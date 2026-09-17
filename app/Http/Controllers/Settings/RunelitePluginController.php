<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Services\RunelitePluginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RunelitePluginController extends Controller
{
    public function show(Request $request, RunelitePluginService $plugin): Response
    {
        $this->ensureAvailable();

        $token = PluginToken::where('user_id', $request->user()->id)->first();

        return Inertia::render('Settings/RunelitePlugin', [
            'mode' => Setting::get('runelite_plugin_mode'),
            'token' => $token === null ? null : [
                'hint' => $token->hint,
                'createdAt' => $token->created_at?->toIso8601String(),
                'lastUsedAt' => $token->last_used_at?->toIso8601String(),
            ],
            'newCode' => $request->session()->get('plugin-code'),
            'osrsUsername' => $request->user()->osrs_username,
            'status' => $plugin->status($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAvailable();

        return back()
            ->with('plugin-code', PluginToken::issueFor($request->user()))
            ->with('board-save', trans('plugin.code_created'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->ensureAvailable();

        PluginToken::where('user_id', $request->user()->id)->delete();

        return back()->with('board-save', trans('plugin.code_revoked'));
    }

    private function ensureAvailable(): void
    {
        abort_if(Setting::get('runelite_plugin_mode') === 'off', 404);
    }
}
