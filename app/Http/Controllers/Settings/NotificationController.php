<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notification settings, and the endpoints the browser talks to.
 *
 * The page has two halves that people confuse constantly, so it renders them
 * as two: **this device** (does this browser have a live subscription) and
 * **what you want to hear about** (per-category, shared by every device).
 * Turning a category off on a laptop is meant to turn it off on the phone
 * too; unsubscribing the laptop is not.
 */
class NotificationController extends Controller
{
    public function show(Request $request, WebPushService $push): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Notifications', [
            'categories' => NotificationCategory::forDisplay(),
            'preferences' => $user->notificationPreferences(),
            'optedOut' => $user->push_opted_out_at !== null,

            // The server's half of the diagnosis. Without it a browser that
            // has done everything right still shows a dead toggle, because
            // the reason it cannot work is on this side — and "it just
            // doesn't work" is the worst possible outcome for a feature that
            // already fails silently in a dozen ways.
            'serverConfigured' => $push->isConfigured(),

            'devices' => $user->pushSubscriptions()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (PushSubscription $row) => [
                    'id' => $row->id,
                    'label' => $row->deviceLabel(),
                    // Never the endpoint itself, and never the keys: the
                    // endpoint is the address a push is delivered to, and the
                    // keys decrypt it. A fingerprint is enough for somebody
                    // to tell their own two devices apart.
                    'fingerprint' => substr(sha1($row->endpoint), 0, 8),
                    'expired' => $row->expired_at !== null,
                    'stale' => $row->vapid_key !== null
                        && $row->vapid_key !== config('webpush.vapid.public_key'),
                    'lastUsed' => $row->last_used_at?->diffForHumans(),
                    'createdAt' => $row->created_at?->toDateString(),
                ])
                ->all(),
        ]);
    }

    /**
     * The public half of the VAPID pair, served rather than bundled.
     *
     * A key compiled into the frontend build goes stale the moment the
     * backend's changes — and since a stale key produces subscriptions that
     * are accepted and never delivered to, that staleness is invisible.
     * Fetching it at subscribe time means there is exactly one copy.
     */
    public function publicKey(WebPushService $push): JsonResponse
    {
        return response()->json([
            'key' => config('webpush.vapid.public_key'),
            'configured' => $push->isConfigured(),
        ]);
    }

    /**
     * Register (or re-register) this browser.
     *
     * **Upsert on the endpoint, never insert.** A browser hands back the same
     * endpoint until permission is revoked, and the client re-posts it on
     * every load to heal the case where the server lost the row — so an
     * insert here would mean a new row per page view and a notification
     * arriving as many times as the app had been opened.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'vapid_key' => config('webpush.vapid.public_key'),
                // Re-registering revives a row a push service had told us to
                // stop using. That is the browser saying the endpoint is good
                // again, which is better evidence than our stale 410.
                'expired_at' => null,
            ],
        );

        // Subscribing is the opposite of the explicit off switch, so it
        // clears it. Without this, someone who turned notifications off and
        // later back on would land in the state the flag exists to describe —
        // permission granted, subscription live, and nothing ever sent.
        $request->user()->update(['push_opted_out_at' => null]);

        return response()->json(['id' => $subscription->id]);
    }

    /**
     * Turn this browser off, and remember that it was deliberate.
     *
     * The flag is the load-bearing half. Dropping the subscription leaves the
     * OS permission granted, which is precisely the state auto-subscribe
     * reads as "granted, so subscribe silently" — so without a stored
     * opt-out, switching notifications off switches them back on at the next
     * page load and the toggle appears broken.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['nullable', 'string', 'max:500'],
        ]);

        $query = $request->user()->pushSubscriptions();

        if (filled($data['endpoint'] ?? null)) {
            $query->where('endpoint', $data['endpoint']);
        }

        $query->delete();

        $request->user()->update(['push_opted_out_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Forget one device from the list — the other-browser case. */
    public function forgetDevice(Request $request, PushSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        $subscription->delete();

        return back()->with('board-save', trans('notifications.device_forgotten'));
    }

    /**
     * Save the per-category answers.
     *
     * Stored sparsely on purpose — only the keys the catalogue knows about,
     * so a stale key posted by an old cached page cannot write itself into
     * the preferences and sit there forever.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        $preferences = collect($data['preferences'])
            ->filter(fn ($value, $key) => NotificationCategory::exists($key))
            ->map(fn ($value) => (bool) $value)
            ->all();

        $request->user()->update(['notification_preferences' => $preferences]);

        return back()->with('board-save', trans('notifications.preferences_saved'));
    }

    /**
     * Send one real notification of a chosen kind to this user's devices.
     *
     * The single most useful thing on the page. Every real trigger is an
     * event you cannot summon — a host approving your claim, a race ending,
     * a sync breaking — so without this the first time anybody sees a given
     * notification is the moment it matters, which is a poor time to find
     * out the deep link was wrong.
     *
     * It deliberately bypasses the category preference and the throttle:
     * this is somebody pressing a button labelled "send me this one", and
     * refusing because they pressed it twice looks exactly like the failure
     * they are trying to diagnose.
     */
    public function preview(Request $request, WebPushService $push): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(NotificationCategory::keys())],
        ]);

        $subscriptions = $request->user()->pushSubscriptions()->active()->get();

        if ($subscriptions->isEmpty()) {
            return back()->with('board-save-error', trans('notifications.preview_no_devices'));
        }

        $result = $push->send($subscriptions, new PushMessage(
            title: trans("notifications.preview_{$data['category']}_title"),
            body: trans("notifications.preview_{$data['category']}_body"),
            // Somewhere that certainly exists. A preview whose deep link
            // 404s teaches the wrong lesson about the real one.
            path: '/settings/notifications',
            category: $data['category'],
            tag: 'preview:'.$data['category'],
        ));

        if ($result['sent'] === 0) {
            return back()->with('board-save-error', $result['skipped'] > 0
                ? trans('notifications.preview_not_configured')
                : trans('notifications.preview_failed'));
        }

        return back()->with('board-save', trans('notifications.preview_sent', ['count' => $result['sent']]));
    }
}
