<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\EventDuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Site-wide settings an admin can change without a deploy.
 *
 * Deliberately NOT here: a maintenance-mode toggle. Laravel already ships
 * one (`php artisan down`), and it works at a layer this can't reach — a
 * second, database-backed flag would still need the app booting and the DB
 * reachable, which is exactly what maintenance mode is usually needed for.
 * Two switches that disagree is worse than one that lives in artisan.
 *
 * `admin_lockdown_enabled` below is not that either, for the same reason:
 * the app keeps running and answering, it just refuses everyone but an
 * admin. See EnsureSiteUnlocked for how it differs from `site_lock_enabled`.
 */
class SiteSettingsController extends Controller
{
    public function show(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $settings = Setting::cached();

        return Inertia::render('Admin/Site', [
            // The hash never leaves the server. The form needs to know only
            // whether one is set, so the field can say "leave blank to keep
            // the current password" rather than rendering a bcrypt string
            // into the page for anyone to copy.
            'settings' => [
                ...$settings,
                'site_lock_password' => null,
                'site_lock_has_password' => ($settings['site_lock_password'] ?? null) !== null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate([
            'registration_open' => ['required', 'boolean'],
            'default_board_size' => ['required', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            // nullable = "unlimited", matching the boards table's own
            // dice_roll_limit convention rather than inventing a sentinel.
            'default_dice_roll_limit' => ['nullable', 'integer', 'min:1', 'max:99'],
            // A duration, not a day count: `10d`, `2w`, `1m`, or a bare
            // number read as days. Capped inside the rule at a year in each
            // unit — this pre-fills a date field, and a default that lands an
            // event's end date in 2031 is a typo nobody notices until the
            // standings never close.
            'default_event_duration' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! EventDuration::isValid($value)) {
                    $fail(trans('validation.event_duration'));
                }
            }],
            'announcement' => ['nullable', 'string', 'max:280'],
            'announcement_type' => ['required', Rule::in(Setting::ANNOUNCEMENT_TYPES)],
            'announcement_public' => ['required', 'boolean'],
            // http/https only, matching what the page renderer's safeHref()
            // will accept anyway — better to reject it at the form than to
            // store a value that silently renders as no button at all.
            'kofi_url' => ['required', 'url:http,https', 'max:255'],
            'discord_webhooks_enabled' => ['required', 'boolean'],
            'site_lock_enabled' => ['required', 'boolean'],
            'admin_lockdown_enabled' => ['required', 'boolean'],
            // Required only when turning the lock on without one already
            // stored — otherwise blank means "keep the current password",
            // which is what an admin editing any other field on this page
            // is doing.
            'site_lock_password' => [
                'nullable',
                'string',
                'min:6',
                'max:255',
                Rule::requiredIf(fn () => $request->boolean('site_lock_enabled') && Setting::get('site_lock_password') === null),
            ],
        ], [], [
            // Without this the message reads "The kofi url field ...", from
            // Laravel's snake_case-to-words fallback.
            'kofi_url' => __('admin.site_kofi_url'),
        ]);

        // Only the validated keys are written, so the request can't
        // introduce a key that isn't a real setting.
        $values = [
            ...$data,
            'announcement' => $data['announcement'] ?: null,
        ];

        // Hashed on the way in, and dropped from the write entirely when the
        // field was left blank — assigning null there would silently clear
        // the password every time an admin saved an unrelated setting.
        if (filled($data['site_lock_password'] ?? null)) {
            $values['site_lock_password'] = Hash::make($data['site_lock_password']);
        } else {
            unset($values['site_lock_password']);
        }

        // Diffed against the current values before writing, so the log holds
        // what actually changed rather than a full copy of the form on every
        // save — otherwise "who closed registration" is buried under dozens
        // of identical no-op rows.
        $before = Setting::cached();
        $changes = [];

        foreach ($values as $key => $value) {
            if (($before[$key] ?? null) === $value) {
                continue;
            }

            // The lock password is the one setting whose VALUE must not
            // reach the audit log — "it changed" is the whole useful fact,
            // and a bcrypt hash sitting in a table admins can read is a
            // credential leak with extra steps.
            $changes[$key] = $key === 'site_lock_password'
                ? ['from' => '********', 'to' => '********']
                : ['from' => $before[$key] ?? null, 'to' => $value];
        }

        Setting::setMany($values);

        if ($changes !== []) {
            AuditLog::record('settings.updated', null, $changes);
        }

        return back()->with('board-save', trans('admin.site_saved'));
    }
}
