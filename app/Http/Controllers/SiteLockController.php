<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureSiteUnlocked;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/** The shared-password door in front of a pre-launch site. */
class SiteLockController extends Controller
{
    /**
     * Union return type, not Symfony's Response: Inertia\Response is a
     * Responsable, not a Response, so declaring the framework's own type
     * here is a TypeError the moment the page actually renders.
     */
    public function show(Request $request): InertiaResponse|RedirectResponse
    {
        // Nothing to unlock. Without this the page is reachable when the
        // lock is off, which reads as though the site is locked when it is
        // not.
        if (! Setting::get('site_lock_enabled') || $request->session()->get(EnsureSiteUnlocked::SESSION_KEY)) {
            return redirect('/');
        }

        return Inertia::render('SiteLock');
    }

    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $hash = Setting::get('site_lock_password');

        // A lock with no password set is not a lock, and refusing every
        // attempt would strand an admin who turned it on before choosing
        // one. The admin form requires a password to enable it; this is the
        // belt to that braces.
        if (! $hash || ! Hash::check($request->string('password')->toString(), $hash)) {
            return back()->withErrors(['password' => trans('lock.wrong_password')]);
        }

        // Regenerated on success, exactly like a login: the pre-unlock
        // session id was handed out to an anonymous visitor, and keeping it
        // means a fixated one survives the unlock.
        $request->session()->regenerate();
        $request->session()->put(EnsureSiteUnlocked::SESSION_KEY, true);

        return redirect()->intended('/');
    }
}
