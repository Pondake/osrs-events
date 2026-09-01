<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The services this account is wired to, on their own settings page.
 *
 * Split out of Settings → Account on 2026-08-30. Account had grown into two
 * unrelated jobs — how you get INTO this account (email, password, closing
 * it) and which outside services it talks to — and a second outside service
 * arriving is what made that obvious. They are separate concerns with
 * separate reasons to be visited, so they are separate pages.
 *
 * Discord's own connect/disconnect still live on DiscordController: that flow
 * is an OAuth round trip and belongs with the rest of it. This page only
 * renders the state and links into it.
 */
class ConnectionsController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('Settings/Connections', [
            'hasDiscord' => $user->discord_id !== null,

            // Needed for the disconnect guard, not for a password field: an
            // account whose only way in is Discord may not unlink it, and the
            // button says so rather than failing on submit.
            'hasPassword' => $user->password !== null,
        ]);
    }
}
