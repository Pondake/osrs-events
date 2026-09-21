<?php

namespace App\Services;

use App\Models\PluginCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Does a plugin report hang together with what this player reported before?
 *
 * The answer is never "no". Everything the client sends can be forged, so a
 * failed check only sends the claim to a host with the reason attached; a
 * false positive costs a player a review, a refusal would cost them the drop.
 * See initialClaimStatus() for where a doubt takes effect.
 */
class PluginPlausibilityService
{
    /** A client clock a little ahead of ours is normal; minutes ahead is not. */
    public const FUTURE_MARGIN_SECONDS = 120;

    /**
     * The client holds unsent reports in memory and retries while it runs, so
     * an honest report is late by as long as the server was unreachable. A
     * stored request body replayed later under a fresh client_event_id is
     * older than that.
     */
    public const MAX_AGE_SECONDS = 6 * 3600;

    /** Faster than any boss is killed, so a bigger step is a gap in the reports or a made-up number. */
    public const MIN_SECONDS_PER_KILL = 5;

    /**
     * Reason codes for this report, empty when it looks fine.
     *
     * @param  array<string, mixed>  $data  the validated request
     * @return list<string>
     */
    public function doubts(User $user, array $data): array
    {
        $occurredAt = Carbon::parse($data['occurred_at']);
        $doubts = [];

        if ($occurredAt->gt(now()->addSeconds(self::FUTURE_MARGIN_SECONDS))) {
            $doubts[] = 'occurred_in_future';
        } elseif ($occurredAt->lt(now()->subSeconds(self::MAX_AGE_SECONDS))) {
            $doubts[] = 'occurred_too_old';
        }

        $killCount = $data['context']['kill_count'] ?? null;

        if ($killCount !== null) {
            $doubts = [...$doubts, ...$this->killCountDoubts($user, $data['context'], (int) $killCount, $occurredAt)];
        }

        return $doubts;
    }

    /**
     * A boss's kill count only goes up, and by no more than the time between
     * two reports allows. Judged against the neighbours in game time rather
     * than in arrival time, so a backlog delivered out of order is not
     * mistaken for a count going down.
     *
     * Nothing to compare against is not a doubt: the first report for a boss
     * has no history to contradict.
     *
     * @return list<string>
     */
    private function killCountDoubts(User $user, array $context, int $killCount, Carbon $occurredAt): array
    {
        // The name over the id: a boss can be several NPC ids (Zulrah changes
        // form) but its kill count is one number.
        [$key, $value] = filled($context['npc_name'] ?? null)
            ? ['npc_name', $context['npc_name']]
            : [filled($context['npc_id'] ?? null) ? 'npc_id' : null, $context['npc_id'] ?? null];

        if ($key === null) {
            return [];
        }

        $history = PluginCompletion::query()
            ->where('user_id', $user->id)
            ->whereNotNull('context->kill_count')
            ->where("context->{$key}", $value);

        $before = (clone $history)->where('occurred_at', '<=', $occurredAt)
            ->orderByDesc('occurred_at')->orderByDesc('created_at')->first();
        $after = (clone $history)->where('occurred_at', '>', $occurredAt)
            ->orderBy('occurred_at')->orderBy('created_at')->first();

        $doubts = [];

        if (($before !== null && $killCount < (int) $before->context['kill_count'])
            || ($after !== null && $killCount > (int) $after->context['kill_count'])) {
            $doubts[] = 'kill_count_dropped';
        }

        if ($before !== null) {
            $elapsed = max(0, $before->occurred_at->diffInSeconds($occurredAt));
            $allowed = intdiv($elapsed, self::MIN_SECONDS_PER_KILL) + 1;

            if ($killCount - (int) $before->context['kill_count'] > $allowed) {
                $doubts[] = 'kill_count_jumped';
            }
        }

        return $doubts;
    }
}
