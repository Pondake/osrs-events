<?php

namespace App\Models\Concerns;

use App\Models\Setting;
use App\Models\User;

/**
 * Shared by Board and BingoCard: where a new claim starts out.
 *
 * Two things decide it. The host's settings say whether this board reviews at
 * all, and whether it trusts what RuneLite reports. On top of that the site
 * asks one question of its own: has a RuneLite client ever reported this
 * account playing the name it claims?
 *
 * That second question exists because nothing else on the site can tell one
 * player's name from another's. Anybody can type a name that belongs to
 * somebody else, so an automatic approval under an unproven name is an
 * approval for whoever typed it first. Rather than take a name away from an
 * account, an unproven name costs the shortcut: the claim goes to a host, who
 * can approve it like any other.
 *
 * Only while the plugin is live. There is no point holding claims for a proof
 * nobody can obtain yet, and flipping this on a site whose hosts switched
 * review off would queue everything they deliberately stopped queueing.
 */
trait ReviewsClaims
{
    public const COMPLETED_VIA = ['MANUAL', 'RUNELITE'];

    public function initialClaimStatus(string $via, ?User $user = null): string
    {
        if ($user !== null && ! $user->hasProvenOsrsName() && Setting::get('runelite_plugin_mode') === 'live') {
            return 'PENDING';
        }

        if (! $this->requires_approval) {
            return 'APPROVED';
        }

        return $via === 'RUNELITE' && $this->trust_runelite_completions ? 'APPROVED' : 'PENDING';
    }
}
