<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\DisplayPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** How the board draws movement, for this account. */
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

    /** Whitelisted against the catalogue, so a stray key is not stored. */
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
