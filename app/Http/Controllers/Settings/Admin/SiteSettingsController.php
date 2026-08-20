<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
 */
class SiteSettingsController extends Controller
{
    public function show(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Settings/Admin/Site', [
            'settings' => Setting::cached(),
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
            'announcement' => ['nullable', 'string', 'max:280'],
            'announcement_type' => ['required', Rule::in(Setting::ANNOUNCEMENT_TYPES)],
            // http/https only, matching what the page renderer's safeHref()
            // will accept anyway — better to reject it at the form than to
            // store a value that silently renders as no button at all.
            'kofi_url' => ['required', 'url:http,https', 'max:255'],
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

        // Diffed against the current values before writing, so the log holds
        // what actually changed rather than a full copy of the form on every
        // save — otherwise "who closed registration" is buried under dozens
        // of identical no-op rows.
        $before = Setting::cached();
        $changes = [];

        foreach ($values as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $value];
            }
        }

        Setting::setMany($values);

        if ($changes !== []) {
            AuditLog::record('settings.updated', null, $changes);
        }

        return back()->with('board-save', trans('admin.site_saved'));
    }
}
