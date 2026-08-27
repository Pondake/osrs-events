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
        $fullLockdown = (bool) Setting::get('admin_lockdown_enabled');
        $isAdmin = (bool) $request->user()?->isAdmin();

        // Full lockdown refuses everyone but an admin, so this page always
        // has something to say to anyone else — including an ordinary
        // account already signed in, who the pre-launch door alone would
        // have let straight through.
        if ($fullLockdown && ! $isAdmin) {
            return Inertia::render('SiteLock', ['fullLockdown' => true]);
        }

        // Past here full lockdown is either off or this is an admin — and
        // either way, anyone already signed in has nothing left to unlock:
        // the pre-launch door lets any existing account straight through.
        if ($request->user() !== null) {
            return redirect('/');
        }

        // Nothing to unlock. Without this the page is reachable when the
        // lock is off, which reads as though the site is locked when it is
        // not.
        if (! Setting::get('site_lock_enabled') || $request->session()->get(EnsureSiteUnlocked::SESSION_KEY)) {
            return redirect('/');
        }

        return Inertia::render('SiteLock', ['fullLockdown' => false]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        // Full lockdown does not accept the shared password at all — it
        // exists so that only an admin gets through. Accepting it here would
        // set the pre-launch door's own session flag and still bounce the
        // visitor straight back to this page on the next request, since
        // EnsureSiteUnlocked::isShutFor() doesn't consult that flag while
        // full lockdown is on — better to say so than to look broken.
        if (Setting::get('admin_lockdown_enabled') && ! $request->user()?->isAdmin()) {
            return back()->withErrors(['password' => trans('lock.full_lockdown_notice')]);
        }

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
