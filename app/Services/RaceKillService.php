<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PluginCompletion;
use App\Models\User;
use App\Support\BossKillNames;
use App\Support\RuneliteName;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * Live boss kills for drop races, from what the RuneLite plugin reports.
 *
 * Wise Old Man stays the source of a standing; this only lets a player who
 * runs the plugin move ahead of its lag, never behind it — the stored number
 * is the higher of the two. A player without the plugin is measured exactly
 * as before, which is the whole reason the hiscores number is kept.
 *
 * Kills are counted by distinct kill count, the same rule as a counted square
 * (TargetProgressService), so a resent report adds nothing. A report the
 * plausibility check doubted is not counted at all: a claim has a host who can
 * approve it, a leaderboard has nobody.
 */
class RaceKillService
{
    /** The names the plugin should report kills of, for the races this player is in. */
    public function watchNames(User $user): Collection
    {
        return $this->activeStandings($user)
            ->map(fn (EventStanding $standing) => BossKillNames::for($standing->event->metric))
            ->filter()
            ->map(fn (string $name) => RuneliteName::normalize($name))
            ->unique()
            ->values();
    }

    /** Count this report toward every race it belongs to. */
    public function record(User $user, PluginCompletion $completion): void
    {
        $killCount = $completion->context['kill_count'] ?? null;

        if ($completion->kind !== 'npc_kill' || $killCount === null || filled($completion->doubts)) {
            return;
        }

        $name = RuneliteName::normalize($completion->name);

        foreach ($this->activeStandings($user) as $standing) {
            // Each character has its own row; the kill is the reporter's.
            if (! RuneliteName::sameRsn($standing->username, $completion->rsn)) {
                continue;
            }

            $boss = BossKillNames::for($standing->event->metric);

            if ($boss === null || RuneliteName::normalize($boss) !== $name || ! $this->inWindow($standing->event, $completion)) {
                continue;
            }

            try {
                $standing->kills()->create(['kill_count' => $killCount, 'plugin_completion_id' => $completion->id]);
            } catch (UniqueConstraintViolationException) {
                // Already counted: the plugin resends.
                continue;
            }

            $live = $standing->kills()->count();

            $standing->forceFill([
                'live_gained' => $live,
                'gained' => max($standing->gained, $live),
            ])->save();
        }
    }

    /** @return Collection<int, EventStanding> */
    private function activeStandings(User $user): Collection
    {
        return EventStanding::query()
            ->where('user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('type', 'DROP_RACE')->whereNull('paused_at'))
            ->with('event')
            ->get()
            // A row whose character has left the account (removed, renamed)
            // keeps the numbers it had and counts nothing new.
            ->reject(fn (EventStanding $standing) => $standing->event->isEnded()
                || $standing->event->isUpcoming()
                || $standing->osrs_account_id === null)
            ->values();
    }

    /** The event's own window, the same one refresh() measures gains over. */
    private function inWindow(Event $event, PluginCompletion $completion): bool
    {
        $at = $completion->occurred_at;

        if ($at === null) {
            return false;
        }

        return ($event->start_date === null || $at->gte($event->start_date->copy()->startOfDay()))
            && ($event->end_date === null || $at->lte($event->end_date->copy()->endOfDay()));
    }
}
