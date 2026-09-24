<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Services\PluginTestReport;
use App\Services\RunelitePluginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RunelitePluginController extends Controller
{
    public function show(Request $request, RunelitePluginService $plugin, PluginTestReport $tests): Response
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
            // The tester's checklist and every report they sent, only while
            // the plugin is in testing.
            'tests' => Setting::get('runelite_plugin_mode') === 'testing' ? $tests->forUser($request->user()) : null,
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

    /** Start the test set over. Only while testing: live events are nobody's to reset. */
    public function resetTests(Request $request, PluginTestReport $tests): RedirectResponse
    {
        abort_unless(Setting::get('runelite_plugin_mode') === 'testing', 404);

        $tests->reset($request->user());

        return back()->with('board-save', trans('plugin_tests.reset_done'));
    }

    private function ensureAvailable(): void
    {
        abort_if(Setting::get('runelite_plugin_mode') === 'off', 404);
    }
}
