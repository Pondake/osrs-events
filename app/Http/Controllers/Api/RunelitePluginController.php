<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PluginCompletion;
use App\Models\Setting;
use App\Services\OsrsIdentityService;
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
            // So the plugin can say the name is not proven yet, and what
            // that costs: claims keep going through a host.
            'proven' => $request->user()->hasProvenOsrsName(),
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

    /**
     * The character this client is signed in as.
     *
     * A match proves the name on the account, in the only sense we can get
     * cheaply: somebody playing that character ran a client that holds this
     * account's code. It is not a signature — a person with their own code
     * can post any name they like here — so it buys protection against a
     * collision and a casual squatter, not against forgery.
     *
     * A mismatch is not an error. Plenty of people have a second character;
     * it simply proves nothing, and says so.
     */
    public function identity(Request $request, OsrsIdentityService $identity): JsonResponse
    {
        $data = $request->validate([
            'rsn' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $matched = $identity->proveFromPlugin($user, $data['rsn']);

        return response()->json([
            'rsn' => $user->osrs_username,
            'matched' => $matched,
            'proven' => $user->fresh()->hasProvenOsrsName(),
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
            // Optional context, so a host reviewing a claim sees more than
            // "the plugin said so". Field by field, and an unknown key is a
            // 422 rather than silently dropped — see the gotcha in
            // docs/runelite-plugin.md. Nothing sensitive: no chat, no other
            // players, region only and never exact coordinates.
            'context' => ['sometimes', 'nullable', 'array', $this->rejectUnknownKeys([
                'source', 'npc_id', 'npc_name', 'npc_level', 'kill_count', 'region_id', 'items',
            ])],
            'context.source' => ['sometimes', 'nullable', Rule::in(['npc_kill', 'loot', 'collection_log', 'kill_count'])],
            'context.npc_id' => ['sometimes', 'nullable', 'integer'],
            'context.npc_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'context.npc_level' => ['sometimes', 'nullable', 'integer'],
            'context.kill_count' => ['sometimes', 'nullable', 'integer'],
            'context.region_id' => ['sometimes', 'nullable', 'integer'],
            'context.items' => ['sometimes', 'nullable', 'array', 'max:40'],
            'context.items.*' => ['array', $this->rejectUnknownKeys(['id', 'name', 'quantity'])],
            'context.items.*.id' => ['required', 'integer'],
            'context.items.*.name' => ['required', 'string', 'max:64'],
            'context.items.*.quantity' => ['required', 'integer', 'min:1'],
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
                $outcome = $plugin->complete($user, $data['name'], $logged);
                $logged->update(['claims' => $outcome['claims'], 'progress' => $outcome['progress']]);

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

    /** A 422 on an unknown key, rather than the strict-validation gotcha of dropping it silently. */
    private function rejectUnknownKeys(array $allowed): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($allowed) {
            if (! is_array($value)) {
                return;
            }

            foreach (array_keys($value) as $key) {
                if (! in_array($key, $allowed, true)) {
                    $fail(trans('plugin.api_context_unknown_key', ['key' => $key]));
                }
            }
        };
    }

    private function answer(PluginCompletion $logged, bool $duplicate): JsonResponse
    {
        return response()->json([
            'client_event_id' => $logged->client_event_id,
            'duplicate' => $duplicate,
            'claims' => $logged->claims ?? [],
            // Counted targets this report moved without claiming, so the
            // plugin can say "2 / 5" instead of nothing at all.
            'progress' => $logged->progress ?? [],
        ], $duplicate ? 200 : 201);
    }
}
