<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

/** "Forgot password" — step 1: request a reset link by email. */
class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always report success, whatever the broker returned — a distinct
        // "no such user" response would turn this into an oracle for probing
        // which email addresses have accounts. The generic message covers
        // INVALID_USER and RESET_THROTTLED alike.
        return back()->with('status', trans('auth.reset_link_sent'));
    }
}
