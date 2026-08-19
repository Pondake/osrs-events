<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Email/password login — see RegisteredUserController for the matching signup flow. */
class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        // Passed explicitly rather than added to HandleInertiaRequests'
        // globally-shared flash bag — only the auth pages ever read it.
        return Inertia::render('Auth/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // A Discord-only account has a null password column — Laravel's
        // Hash::check() explicitly short-circuits to false for a null/empty
        // hashedValue rather than erroring, so this already fails safely
        // for those accounts without needing a separate whereNotNull guard.
        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.login_failed'),
            ]);
        }

        // Session fixation prevention — see RegisteredUserController's note.
        $request->session()->regenerate();

        return redirect()->intended('/boards');
    }
}
