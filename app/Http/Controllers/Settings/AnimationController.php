<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\DisplayPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * How the board draws movement, for this account.
 *
 * Its own page rather than a card on the profile: a profile is who you are,
 * and this is how the game behaves while you watch it. It is also the setting
 * most likely to grow — a board full of pieces walking at once is exactly the
 * sort of thing people want dialled down in more than one way.
 */
class AnimationController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Settings/Animations', [
            // Resolved rather than raw: a list saved before a setting existed
            // must not read as that setting being off.
            'preferences' => DisplayPreference::resolve($request->user()->display_preferences),
            'keys' => array_keys(DisplayPreference::ALL),
        ]);
    }

    /**
     * Whitelisted against the catalogue rather than stored as sent — an
     * unknown key would sit in the JSON forever, doing nothing and looking
     * like a setting somebody removed the UI for.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        $preferences = collect($data['preferences'])
            ->filter(fn ($value, $key) => array_key_exists($key, DisplayPreference::ALL))
            ->map(fn ($value) => (bool) $value)
            ->all();

        $request->user()->update(['display_preferences' => $preferences]);

        return back()->with('board-save', trans('animations.saved'));
    }
}
