<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\DiagnosticsService;
use App\Services\WebPushService;
use App\Services\WiseOldManService;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * The "why is nothing happening" page.
 *
 * Everything here existed as an artisan command first, which was the wrong
 * shape for the job: the moments you need it are a deploy that went quiet and
 * a phone that stopped buzzing, and neither is a moment anyone wants to be
 * hunting for an SSH session and a site directory owned by another system
 * user. The checks are the same — DiagnosticsService is the single source and
 * `push:doctor` prints its push half — so the two surfaces cannot disagree.
 *
 * **Read-only by default.** Every check is a question. The three buttons that
 * do something are each addressed at the admin pressing them (a push to their
 * own devices, a mail to their own address) or are explicitly a rehearsal
 * (the sweep's dry run). Nothing here can reach another user, which is what
 * makes it safe to put behind an ordinary admin login rather than a
 * confirmation dialog.
 */
class DiagnosticsController extends Controller
{
    public function index(Request $request, DiagnosticsService $diagnostics): Response
    {
        return Inertia::render('Admin/Diagnostics', [
            'groups' => $diagnostics->all(),
            'categories' => NotificationCategory::forDisplay(),

            // This admin's own devices, so "send a test" can say where it
            // went. Fingerprints rather than endpoints: an endpoint is the
            // address a push is delivered to, and this page is meant to be
            // screenshotted into a chat.
            'myDevices' => $this->devicesFor($request),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function devicesFor(Request $request): array
    {
        return $request->user()->pushSubscriptions()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PushSubscription $row) => [
                'id' => $row->id,
                'label' => $row->deviceLabel(),
                'fingerprint' => substr(sha1($row->endpoint), 0, 8),
                'expired' => $row->expired_at !== null,
                'lastUsed' => $row->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Send one real notification to the admin's own devices.
     *
     * A real encrypted push through the real service, not a simulation —
     * because the half that breaks is the half a simulation would skip. It is
     * the only way to prove the whole chain (keys, encryption, the push
     * service, the service worker, the tap) on a specific device, and the
     * last hop is the one that cannot be checked any other way.
     */
    public function testPush(Request $request, WebPushService $push): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(NotificationCategory::keys())],
        ]);

        $subscriptions = $request->user()->pushSubscriptions()->active()->get();

        if ($subscriptions->isEmpty()) {
            return back()->with('board-save-error', trans('diagnostics.test_push_no_devices'));
        }

        $result = $push->send($subscriptions, new PushMessage(
            title: trans("notifications.preview_{$data['category']}_title"),
            body: trans("notifications.preview_{$data['category']}_body"),
            // Back here, so tapping it proves the deep link lands somewhere
            // that exists — a test whose link 404s teaches the wrong lesson
            // about the real ones.
            path: '/admin/diagnostics',
            category: $data['category'],
            tag: 'diagnostics:'.$data['category'],
        ));

        if ($result['sent'] === 0) {
            return back()->with('board-save-error', $result['skipped'] > 0
                ? trans('diagnostics.test_push_unconfigured')
                : trans('diagnostics.test_push_failed'));
        }

        return back()->with('board-save', trans('diagnostics.test_push_sent', [
            'count' => $result['sent'],
            'expired' => $result['expired'],
        ]));
    }

    /**
     * Prove mail transport, to the admin's own address.
     *
     * Deliberately a plain message rather than one of the app's themed
     * mailables: the question this answers is whether anything leaves the
     * server at all, and wrapping it in a template only adds a second thing
     * that could be the reason it did not.
     *
     * With `MAIL_MAILER=log` this reports success and writes to a file, which
     * is exactly the trap — so the page warns about that driver separately,
     * above, rather than pretending this button can detect it.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $address = $request->user()->email;

        if (blank($address)) {
            // The common case for this app rather than an edge one: Discord
            // login never asks for an address.
            return back()->with('board-save-error', trans('diagnostics.test_mail_no_address'));
        }

        try {
            Mail::raw(trans('diagnostics.test_mail_body', ['app' => config('app.name')]), function ($message) use ($address) {
                $message->to($address)->subject(trans('diagnostics.test_mail_subject'));
            });
        } catch (Throwable $error) {
            report($error);

            return back()->with('board-save-error', trans('diagnostics.test_mail_failed', [
                'error' => $error->getMessage(),
            ]));
        }

        return back()->with('board-save', trans('diagnostics.test_mail_sent', ['address' => $address]));
    }

    /**
     * One live lookup against Wise Old Man.
     *
     * Their API is where every race's numbers come from, and when it is
     * unreachable the only symptom is a leaderboard that stops moving. This
     * asks it directly, with a name the admin types, so "is it them or is it
     * us" has an answer that takes five seconds.
     */
    public function checkWom(Request $request, WiseOldManService $wom): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:12'],
        ]);

        $result = $wom->findPlayer($data['username']);

        // Three outcomes, not two, and the difference is the whole point: a
        // player their API has never heard of is a working API, and reporting
        // that as a failure would send someone debugging their own server.
        return match ($result['found']) {
            true => back()->with('board-save', trans('diagnostics.wom_check_found', [
                'name' => $result['displayName'] ?? $data['username'],
            ])),
            false => back()->with('board-save', trans('diagnostics.wom_check_unknown', [
                'name' => $data['username'],
            ])),
            default => back()->with('board-save-error', trans('diagnostics.wom_check_unreachable')),
        };
    }

    /**
     * Rehearse the notification sweep and show its output.
     *
     * Dry run only, and that is not timidity: the real sweep sends to other
     * people, and a button that buzzes thirty phones is not a diagnostic. The
     * scheduler owns the real run — if it is not running, the fix is the cron
     * entry, which the Scheduled work checks above already name.
     */
    public function sweep(): RedirectResponse
    {
        Artisan::call('push:sweep', ['--dry-run' => true]);

        return back()->with('sweepOutput', trim(Artisan::output()));
    }
}
