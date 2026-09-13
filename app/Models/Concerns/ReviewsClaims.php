<?php

namespace App\Models\Concerns;

/**
 * Shared by Board and BingoCard: where a new claim starts out.
 *
 * A RuneLite completion only skips review when the host opted in for this
 * board; otherwise it goes through the same queue as a screenshot.
 */
trait ReviewsClaims
{
    public const COMPLETED_VIA = ['MANUAL', 'RUNELITE'];

    public function initialClaimStatus(string $via): string
    {
        if (! $this->requires_approval) {
            return 'APPROVED';
        }

        return $via === 'RUNELITE' && $this->trust_runelite_completions ? 'APPROVED' : 'PENDING';
    }
}
