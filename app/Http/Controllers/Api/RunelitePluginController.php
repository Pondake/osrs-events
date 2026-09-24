<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PluginCompletion;
use App\Models\Setting;
use App\Models\User;
use App\Services\OsrsIdentityService;
use App\Services\PluginPlausibilityService;
use App\Services\RaceKillService;
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
    public function events(Request $request, RunelitePluginService $plugin, RaceKillService $races): JsonResponse
    {
        $events = $plugin->openTargets($request->user());

        return response()->json([
            'mode' => Setting::get('runelite_plugin_mode'),
            'rsn' => $request->user()->osrs_username,
            // So the plugin can say the name is not proven yet, and what
            // that costs: claims keep going through a host.
            'proven' => $request->user()->hasProvenOsrsName(),
            // Every character the plugin may report from, main first. `rsn`
            // and `proven` above stay the main's, for plugins that predate
            // alts.
            'characters' => self::characters($request->user()),
            'max_characters' => OsrsIdentityService::maxCharacters(),
            'events' => $events->map(fn (array $row) => [
                'id' => $row['event']->id,
                'title' => $row['event']->title,
                'type' => $row['event']->type,
                'url' => url("/events/{$row['event']->id}"),
                // This account's (or team's) place, once it has finished.
                'finish' => $plugin->finish($row['event'], $request->user()),
                'targets' => $row['targets']->map(fn (array $target) => RunelitePluginService::describe($target))->all(),
            ])->all(),
            'reviews' => $plugin->recentVerdicts($request->user()),
            // Running races this player is in, for the panel. Absent on a
            // server that predates it; the plugin reads that as none.
            'races' => $plugin->races($request->user()),
            // Upcoming, paused, or ended in the last week. A separate key so
            // a plugin that counts `events` as running ones stays right.
            'other_events' => $plugin->otherEvents($request->user()),
            // One flat list of names, as the shipped plugin reads it. A drop
            // race's boss is in here without being a target: it claims
            // nothing, it moves a leaderboard, and the plugin needs no change
            // to report a kill of a name it is watching.
            'watch' => $events->flatMap(fn (array $row) => $row['targets']->pluck('match'))
                ->merge($races->watchNames($request->user()))
                ->unique()->sort()->values()->all(),
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
            // The plugin's "add new characters as alts" box. Absent is on,
            // so a plugin that predates the box adds them.
            'add_alt' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $result = $identity->proveFromPlugin($user, $data['rsn'], $data['add_alt'] ?? true);
        $user = $user->fresh();

        return response()->json([
            'rsn' => $user->osrs_username,
            ...$result,
            'proven' => $user->hasProvenOsrsName(),
            'characters' => self::characters($user),
        ]);
    }

    public function complete(Request $request, RunelitePluginService $plugin, PluginPlausibilityService $plausibility, RaceKillService $races): JsonResponse
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

        $known = $user->osrsAccounts()->get()->contains(fn ($account) => RuneliteName::sameRsn($data['rsn'], $account->username));

        if (! $known) {
            return response()->json([
                'message' => blank($user->osrs_username)
                    ? trans('plugin.api_rsn_missing')
                    : trans('plugin.api_rsn_mismatch', ['rsn' => $data['rsn'], 'expected' => $user->osrs_username]),
            ], 422);
        }

        // Never a refusal: a doubtful report is stored with its reasons and
        // its claims wait for a host, see initialClaimStatus().
        $doubts = $plausibility->doubts($user, $data);

        try {
            $logged = DB::transaction(function () use ($request, $user, $data, $plugin, $doubts, $races) {
                $logged = PluginCompletion::create([
                    ...$data,
                    'doubts' => $doubts ?: null,
                    'user_id' => $user->id,
                    'plugin_version' => $request->attributes->get('plugin_version'),
                ]);
                $outcome = $plugin->complete($user, $data['name'], $logged);
                $logged->update(['claims' => $outcome['claims'], 'progress' => $outcome['progress'], 'finishes' => $outcome['finishes']]);

                // A kill can be both: the square it claims and the race it
                // moves are different questions about the same report.
                $races->record($user, $logged);

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

    /** @return list<array{rsn: string, main: bool, proven: bool}> */
    private static function characters(User $user): array
    {
        return $user->osrsAccounts()->get()->map(fn ($account) => [
            'rsn' => $account->username,
            'main' => $account->isMain(),
            'proven' => $account->osrs_proven_at !== null,
        ])->all();
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
            // The events this report finished for the account or its team.
            'finishes' => $logged->finishes ?? [],
        ], $duplicate ? 200 : 201);
    }
}
