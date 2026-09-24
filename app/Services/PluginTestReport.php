<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\EventFinish;
use App\Models\EventParticipant;
use App\Models\PluginCompletion;
use App\Models\PluginTester;
use App\Models\PluginToken;
use App\Models\TargetProgress;
use App\Models\User;
use App\Support\PluginTestSet;
use App\Support\RuneliteName;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Expected reports against received ones, per tester and per scenario of
 * PluginTestSet. One service for the admin page and the tester's own
 * checklist, so the two can never disagree.
 *
 * Judged from plugin_completions alone: a report is what the plugin sent,
 * whatever it claimed. Every report since the tester's start is shown, the
 * ones no scenario expects included.
 */
class PluginTestReport
{
    public function event(): ?Event
    {
        return Event::where('title', PluginTestSet::EVENT_TITLE)->with('bingoCard')->first();
    }

    /** @return list<array> every tester, most recently active first */
    public function all(): array
    {
        $event = $this->event();

        if ($event === null) {
            return [];
        }

        $ids = EventParticipant::where('event_id', $event->id)->pluck('user_id')
            ->merge(PluginTester::pluck('user_id'))
            ->unique();

        return User::whereIn('id', $ids)->get()
            ->map(fn (User $user) => $this->forUser($user, $event))
            ->sortByDesc(fn (array $row) => $row['lastReportAt'] ?? '')
            ->values()
            ->all();
    }

    public function forUser(User $user, ?Event $event = null): array
    {
        $event ??= $this->event();
        $since = $event === null ? null : $this->since($user, $event);
        $token = PluginToken::where('user_id', $user->id)->first();

        $reports = $since === null ? collect() : PluginCompletion::where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        $matched = collect();
        $scenarios = [];

        foreach (PluginTestSet::scenarios() as $key => $scenario) {
            if ($key === 'connect') {
                $scenarios[] = $this->connect($user, $token, $since);

                continue;
            }

            $expectations = collect($scenario['expect'])->map(function (array $expect) use ($reports, $event, &$matched) {
                $matching = $reports->filter(fn (PluginCompletion $report) => self::matches($expect, $report));
                $matched = $matched->merge($matching->pluck('id'));

                return $this->judge($expect, $matching, $event);
            });

            $scenarios[] = [
                'key' => $key,
                'optional' => $scenario['optional'],
                'status' => self::overall($expectations->pluck('status')),
                'expectations' => $expectations->all(),
            ];
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->displayName(),
                'rsn' => $user->osrs_username,
            ],
            'joined' => $since !== null,
            'since' => $since?->toIso8601String(),
            'pluginVersion' => $token?->last_plugin_version,
            'lastReportAt' => $reports->last()?->created_at?->toIso8601String(),
            'scenarios' => $scenarios,
            'other' => $reports->reject(fn (PluginCompletion $report) => $matched->contains($report->id))
                ->reverse()->take(50)->map(fn (PluginCompletion $report) => self::report($report))->values()->all(),
        ];
    }

    /**
     * Start over: this tester's claims, counts and finish on the test card go,
     * so every square is open to the plugin again, and the checklist counts
     * from now. Nothing of anybody else's is touched.
     */
    public function reset(User $user): void
    {
        $event = $this->event();

        if ($event === null) {
            return;
        }

        DB::transaction(function () use ($user, $event) {
            $squares = $event->bingoCard?->squares()->pluck('id') ?? collect();

            BingoCompletion::whereIn('bingo_square_id', $squares)->where('user_id', $user->id)->delete();
            TargetProgress::where('kind', 'bingo_square')->whereIn('target_id', $squares)
                ->where('competitor_key', TargetProgressService::bingoKey(['team_id' => null, 'user_id' => $user->id]))
                ->delete();
            EventFinish::where('event_id', $event->id)->where('user_id', $user->id)->delete();

            PluginTester::updateOrCreate(['user_id' => $user->id], ['started_at' => now()]);
        });
    }

    private function since(User $user, Event $event): ?CarbonInterface
    {
        $joined = EventParticipant::where('event_id', $event->id)->where('user_id', $user->id)->first()?->created_at;

        if ($joined === null) {
            return null;
        }

        $restarted = PluginTester::where('user_id', $user->id)->first()?->started_at;

        return $restarted !== null && $restarted->gt($joined) ? $restarted : $joined;
    }

    private function connect(User $user, ?PluginToken $token, ?CarbonInterface $since): array
    {
        $used = $token?->last_used_at !== null && ($since === null || $token->last_used_at->gte($since->copy()->subMinute()));
        $proven = $user->osrsAccounts()->whereNotNull('osrs_proven_at')->exists();

        $problems = array_values(array_filter([
            $token === null ? 'no_code' : null,
            $token !== null && ! $used ? 'not_connected' : null,
            ! $proven ? 'not_proven' : null,
            $used && $token->last_plugin_version === null ? 'no_version' : null,
        ]));

        return [
            'key' => 'connect',
            'optional' => false,
            'status' => $token === null ? 'missing' : ($problems === [] ? 'ok' : 'partial'),
            'expectations' => [[
                'kind' => null,
                'names' => [],
                'source' => null,
                'status' => $token === null ? 'missing' : ($problems === [] ? 'ok' : 'partial'),
                'problems' => $problems,
                'lastUsedAt' => $token?->last_used_at?->toIso8601String(),
                'reports' => [],
            ]],
        ];
    }

    private static function matches(array $expect, PluginCompletion $report): bool
    {
        if ($report->kind !== $expect['kind']) {
            return false;
        }

        if ($expect['source'] !== null && ($report->context['source'] ?? null) !== $expect['source']) {
            return false;
        }

        $name = RuneliteName::normalize($report->name);

        return collect($expect['names'])->contains(fn (string $expected) => RuneliteName::normalize($expected) === $name);
    }

    private function judge(array $expect, Collection $matching, ?Event $event): array
    {
        $count = $expect['count'] ?? 1;
        $qualifying = $matching->filter(fn (PluginCompletion $report) => $report->quantity >= ($expect['min_quantity'] ?? 1));
        $problems = [];

        if ($matching->isNotEmpty()) {
            if ($qualifying->isEmpty() && ($expect['min_quantity'] ?? 1) > 1) {
                $problems[] = ['code' => 'too_small', 'have' => $matching->max('quantity'), 'need' => $expect['min_quantity']];
            } elseif ($qualifying->count() < $count) {
                $problems[] = ['code' => 'too_few', 'have' => $qualifying->count(), 'need' => $count];
            }

            $missing = collect($expect['fields'])
                ->filter(fn (string $field) => $qualifying->contains(fn (PluginCompletion $report) => blank($report->context[$field] ?? null)))
                ->values();

            if ($missing->isNotEmpty()) {
                $problems[] = ['code' => 'missing_fields', 'fields' => $missing->all()];
            }

            $outcome = $expect['outcome'] ?? null;
            $answered = $qualifying->contains(fn (PluginCompletion $report) => collect($outcome === 'claim' ? $report->claims : $report->progress)
                ->contains(fn (array $entry) => $event === null || ($entry['event_id'] ?? null) === $event->id));

            // Not before enough have arrived: the server is right to wait.
            if ($outcome !== null && $qualifying->count() >= $count && ! $answered) {
                $problems[] = ['code' => $outcome === 'claim' ? 'no_claim' : 'no_progress'];
            }

            if ($qualifying->contains(fn (PluginCompletion $report) => filled($report->doubts))) {
                $problems[] = ['code' => 'doubted'];
            }
        }

        return [
            'kind' => $expect['kind'],
            'names' => $expect['names'],
            'source' => $expect['source'],
            'fields' => $expect['fields'],
            'minQuantity' => $expect['min_quantity'] ?? 1,
            'count' => $count,
            'outcome' => $expect['outcome'] ?? null,
            'status' => $matching->isEmpty() ? 'missing' : ($problems === [] ? 'ok' : 'partial'),
            'problems' => $problems,
            'reports' => $matching->reverse()->take(10)->map(fn (PluginCompletion $report) => self::report($report))->values()->all(),
        ];
    }

    private static function overall(Collection $statuses): string
    {
        if ($statuses->every(fn (string $status) => $status === 'ok')) {
            return 'ok';
        }

        return $statuses->every(fn (string $status) => $status === 'missing') ? 'missing' : 'partial';
    }

    private static function report(PluginCompletion $report): array
    {
        $context = $report->context ?? [];

        return [
            'id' => $report->id,
            'kind' => $report->kind,
            'name' => $report->name,
            'quantity' => $report->quantity,
            'rsn' => $report->rsn,
            'pluginVersion' => $report->plugin_version,
            'occurredAt' => $report->occurred_at?->toIso8601String(),
            'createdAt' => $report->created_at?->toIso8601String(),
            'source' => $context['source'] ?? null,
            'context' => collect($context)->except('items')->all(),
            'items' => collect($context['items'] ?? [])->map(fn (array $item) => ($item['quantity'] ?? 1).'× '.($item['name'] ?? '?'))->all(),
            'doubts' => $report->doubts ?? [],
            'claims' => collect($report->claims ?? [])->map(fn (array $claim) => [
                'label' => $claim['label'] ?? $claim['name'] ?? null,
                'eventTitle' => $claim['event_title'] ?? null,
                'status' => $claim['status'] ?? null,
            ])->all(),
            'progress' => collect($report->progress ?? [])->map(fn (array $entry) => [
                'label' => $entry['label'] ?? $entry['name'] ?? null,
                'done' => $entry['done'] ?? null,
                'required' => $entry['required_count'] ?? null,
            ])->all(),
        ];
    }
}
