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
 * Everything the plugin sent, per account, and the same reports judged
 * against the scenarios of PluginTestSet. One service for the admin page and
 * the tester's own page, so the two can never disagree.
 *
 * A tester is anybody with a plugin code or a report, whatever event they
 * played: the test card is a shared starting point, not a boundary. A report
 * counts for a scenario wherever it claimed.
 */
class PluginTestReport
{
    public const LOG_LIMIT = 200;

    public function event(): ?Event
    {
        return Event::where('title', PluginTestSet::EVENT_TITLE)->with('bingoCard')->first();
    }

    /** @return list<array> one line per account that ever used the plugin, most recently active first */
    public function testers(): array
    {
        $ids = PluginToken::pluck('user_id')->merge(PluginCompletion::distinct()->pluck('user_id'))->unique();

        return User::whereIn('id', $ids)->get()
            ->map(fn (User $user) => $this->summary($user))
            ->sortByDesc(fn (array $row) => max($row['lastReportAt'] ?? '', $row['lastUsedAt'] ?? ''))
            ->values()
            ->all();
    }

    public function summary(User $user): array
    {
        $token = PluginToken::where('user_id', $user->id)->first();
        $reports = PluginCompletion::where('user_id', $user->id)->orderBy('created_at')->get();

        return [
            'user' => ['id' => $user->id, 'name' => $user->displayName(), 'rsn' => $user->osrs_username],
            'characters' => $user->osrsAccounts()->get()->map(fn ($account) => [
                'rsn' => $account->username,
                'proven' => $account->osrs_proven_at !== null,
            ])->all(),
            'pluginVersion' => $token?->last_plugin_version,
            'lastUsedAt' => $token?->last_used_at?->toIso8601String(),
            'reportCount' => $reports->count(),
            'claimCount' => $reports->sum(fn (PluginCompletion $report) => count($report->claims ?? [])),
            'doubtedCount' => $reports->filter(fn (PluginCompletion $report) => filled($report->doubts))->count(),
            // Events a report claimed or counted in: the test card is one,
            // everything a tester made or joined themselves the rest.
            'eventCount' => $reports->flatMap(fn (PluginCompletion $report) => [...($report->claims ?? []), ...($report->progress ?? [])])
                ->pluck('event_id')->filter()->unique()->count(),
            'lastReportAt' => $reports->last()?->created_at?->toIso8601String(),
            'scenarios' => collect($this->scenarios($user, $token, $reports))
                ->map(fn (array $scenario) => ['key' => $scenario['key'], 'status' => $scenario['status']])
                ->all(),
        ];
    }

    /** The summary plus every scenario in full and the report log. */
    public function forUser(User $user): array
    {
        $token = PluginToken::where('user_id', $user->id)->first();
        $reports = PluginCompletion::where('user_id', $user->id)->orderBy('created_at')->get();
        $event = $this->event();

        return [
            ...$this->summary($user),
            'since' => $this->since($user)?->toIso8601String(),
            'testSet' => $event === null ? null : [
                'url' => "/events/{$event->id}",
                'joined' => EventParticipant::where('event_id', $event->id)->where('user_id', $user->id)->exists(),
            ],
            'scenarioDetails' => $this->scenarios($user, $token, $reports),
            'log' => $reports->reverse()->take(self::LOG_LIMIT)->map(fn (PluginCompletion $report) => self::report($report))->values()->all(),
        ];
    }

    /**
     * Start over: the checklist counts from now, and this tester's claims,
     * counts and finish on the test card go so every square there is open to
     * the plugin again. Nothing of anybody else's is touched, and the report
     * log itself is kept.
     */
    public function reset(User $user): void
    {
        $event = $this->event();

        DB::transaction(function () use ($user, $event) {
            if ($event !== null) {
                $squares = $event->bingoCard?->squares()->pluck('id') ?? collect();

                BingoCompletion::whereIn('bingo_square_id', $squares)->where('user_id', $user->id)->delete();
                TargetProgress::where('kind', 'bingo_square')->whereIn('target_id', $squares)
                    ->where('competitor_key', TargetProgressService::bingoKey(['team_id' => null, 'user_id' => $user->id]))
                    ->delete();
                EventFinish::where('event_id', $event->id)->where('user_id', $user->id)->delete();
            }

            PluginTester::updateOrCreate(['user_id' => $user->id], ['started_at' => now()]);
        });
    }

    private function since(User $user): ?CarbonInterface
    {
        return PluginTester::where('user_id', $user->id)->first()?->started_at;
    }

    private function scenarios(User $user, ?PluginToken $token, Collection $reports): array
    {
        $since = $this->since($user);
        $reports = $since === null ? $reports : $reports->filter(fn (PluginCompletion $report) => $report->created_at->gte($since));
        $scenarios = [];

        foreach (PluginTestSet::scenarios() as $key => $scenario) {
            if ($key === 'connect') {
                $scenarios[] = $this->connect($user, $token, $since);

                continue;
            }

            $expectations = collect($scenario['expect'])
                ->map(fn (array $expect) => $this->judge($expect, $reports->filter(fn (PluginCompletion $report) => self::matches($expect, $report))));

            $scenarios[] = [
                'key' => $key,
                'status' => self::overall($expectations->pluck('status')),
                'expectations' => $expectations->all(),
            ];
        }

        return $scenarios;
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

        $status = $token === null ? 'missing' : ($problems === [] ? 'ok' : 'partial');

        return [
            'key' => 'connect',
            'status' => $status,
            'expectations' => [[
                'kind' => null,
                'names' => [],
                'source' => null,
                'fields' => [],
                'status' => $status,
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

    private function judge(array $expect, Collection $matching): array
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
            // Any event: a tester's own card counts as much as the test set.
            $answered = $qualifying->contains(fn (PluginCompletion $report) => match ($outcome) {
                'claim' => filled($report->claims),
                'progress' => filled($report->progress),
                default => filled($report->claims) || filled($report->progress),
            });

            // Not before enough have arrived: the server is right to wait.
            if ($outcome !== null && $qualifying->count() >= $count && ! $answered) {
                $problems[] = ['code' => "no_{$outcome}"];
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
