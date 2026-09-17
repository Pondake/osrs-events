<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PluginCompletion;
use App\Models\Setting;
use App\Services\RunelitePluginService;
use App\Support\RuneliteName;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** The API the RuneLite plugin talks to. Contract: docs/runelite-plugin.md. */
class RunelitePluginController extends Controller
{
    public function events(Request $request, RunelitePluginService $plugin): JsonResponse
    {
        $events = $plugin->openTargets($request->user());

        return response()->json([
            'mode' => Setting::get('runelite_plugin_mode'),
            'rsn' => $request->user()->osrs_username,
            'events' => $events->map(fn (array $row) => [
                'id' => $row['event']->id,
                'title' => $row['event']->title,
                'type' => $row['event']->type,
                'url' => url("/events/{$row['event']->id}"),
                'targets' => $row['targets']->map(fn (array $target) => RunelitePluginService::describe($target))->all(),
            ])->all(),
            'reviews' => $plugin->recentVerdicts($request->user()),
            'watch' => $events->flatMap(fn (array $row) => $row['targets']->pluck('match'))->unique()->sort()->values()->all(),
        ]);
    }

    public function complete(Request $request, RunelitePluginService $plugin): JsonResponse
    {
        $data = $request->validate([
            'client_event_id' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(PluginCompletion::KINDS)],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'rsn' => ['required', 'string', 'max:32'],
            'occurred_at' => ['required', 'date'],
        ]);

        $user = $request->user();

        $existing = PluginCompletion::where('user_id', $user->id)->where('client_event_id', $data['client_event_id'])->first();
        if ($existing !== null) {
            return $this->answer($existing, true);
        }

        if (! RuneliteName::sameRsn($data['rsn'], $user->osrs_username)) {
            return response()->json([
                'message' => blank($user->osrs_username)
                    ? trans('plugin.api_rsn_missing')
                    : trans('plugin.api_rsn_mismatch', ['rsn' => $data['rsn'], 'expected' => $user->osrs_username]),
            ], 422);
        }

        try {
            $logged = DB::transaction(function () use ($user, $data, $plugin) {
                $logged = PluginCompletion::create([...$data, 'user_id' => $user->id]);
                $logged->update(['claims' => $plugin->complete($user, $data['name'])]);

                return $logged;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->answer(
                PluginCompletion::where('user_id', $user->id)->where('client_event_id', $data['client_event_id'])->firstOrFail(),
                true,
            );
        }

        return $this->answer($logged, false);
    }

    private function answer(PluginCompletion $logged, bool $duplicate): JsonResponse
    {
        return response()->json([
            'client_event_id' => $logged->client_event_id,
            'duplicate' => $duplicate,
            'claims' => $logged->claims ?? [],
        ], $duplicate ? 200 : 201);
    }
}
