<?php

namespace App\Services;

use App\Models\User;
use App\Support\RuneliteName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Owns the act of putting an OSRS username on an account.
 *
 * Three places set one — registration, the post-login gate, and profile
 * settings — and each needs the same three things to happen: store it, ask
 * Wise Old Man whether it exists, and record the answer. Duplicating that is
 * how one of them ends up skipping the lookup.
 *
 * The check is a **warning, not a gate**. Wise Old Man only knows accounts
 * somebody has looked up there at least once, so a perfectly real player who
 * has never been searched returns a 404 — refusing the name would lock out
 * exactly the newcomers this app wants. So the name is always saved, and an
 * unconfirmed one is something the UI keeps mentioning rather than something
 * that blocks anyone.
 */
class OsrsIdentityService
{
    public function __construct(private readonly WiseOldManService $wom) {}

    /**
     * Whether another account has PROVED this name with the plugin.
     *
     * The line between a warning and a refusal — see RsnNotProvenByAnother.
     * A null user is a registration, where there is no account yet to
     * exclude.
     */
    public function provenByAnother(?User $user, string $username): bool
    {
        return $this->sameRsnQuery($user, $username)
            ->whereNotNull('osrs_proven_at')
            ->exists();
    }

    /**
     * Ask Wise Old Man about a name without storing anything.
     *
     * @return array{found: bool|null, displayName: ?string}
     */
    public function look(string $username): array
    {
        return $this->wom->findPlayer(trim($username));
    }

    /**
     * Whether another account already carries this name.
     *
     * Not a unique index and not a refusal — that decision is written down
     * in the migration that made standings unique per event: a global unique
     * would let whoever types a name first keep somebody else out of their
     * own RSN. This only makes the situation visible, so the second person
     * to arrive is told rather than left wondering why a race says they are
     * already entered.
     *
     * Compared the way the game does: spaces, underscores and hyphens are
     * the same character (RuneliteName::sameRsn), normalised in SQL so this
     * stays one query.
     */
    public function takenByAnother(?User $user, string $username): bool
    {
        return $this->sameRsnQuery($user, $username)->exists();
    }

    /** Every OTHER account carrying this name, compared the way the game does. */
    private function sameRsnQuery(?User $user, string $username): Builder
    {
        $normalised = mb_strtolower(trim(preg_replace('/[\s_\-]+/u', ' ', $username)));

        $query = User::query()
            ->whereNotNull('osrs_username')
            ->whereRaw("lower(replace(replace(osrs_username, '_', ' '), '-', ' ')) = ?", [$normalised]);

        if ($normalised === '') {
            // Matches nothing rather than everything — a blank name is not a
            // claim on anything.
            $query->whereRaw('1 = 0');
        }

        if ($user !== null) {
            $query->whereKeyNot($user->id);
        }

        return $query;
    }

    /**
     * Store a username and check it, returning what Wise Old Man said.
     *
     * @return bool|null true found, false not found, null couldn't tell
     */
    public function apply(User $user, string $username): ?bool
    {
        $username = trim($username);

        $result = $this->wom->findPlayer($username);

        // Prefer their canonical casing over whatever was typed. The hiscores
        // are case-insensitive but a leaderboard is read by people, and
        // "Pondake" beats "pondake" or "PONDAKE".
        if ($result['found'] === true && filled($result['displayName'])) {
            $username = $result['displayName'];
        }

        // The proof belongs to the name it was made about. A rename hands the
        // account a name nobody has played from a client yet, so it starts
        // over — otherwise renaming would be the way around the whole check.
        // Re-saving the same name (the recheck button, the canonical-casing
        // rewrite) is not a rename and keeps it.
        $keepsProof = RuneliteName::sameRsn($user->osrs_username, $username);

        $user->forceFill([
            'osrs_username' => $username,
            'osrs_proven_at' => $keepsProof ? $user->osrs_proven_at : null,
            'osrs_proven_via' => $keepsProof ? $user->osrs_proven_via : null,
            // Only a confirmed hit sets this. A null answer (their API was
            // unreachable) deliberately leaves the account unconfirmed rather
            // than assuming the best — the recurring notice is a nudge to try
            // again, which costs nothing, where a wrong "verified" is a
            // player quietly missing from every leaderboard.
            'osrs_verified_at' => $result['found'] === true ? Carbon::now() : null,
        ])->save();

        return $result['found'];
    }

    /**
     * Record that a RuneLite client reported this account logged in as the
     * name on it.
     *
     * Only a match counts, and a mismatch changes nothing rather than
     * clearing an older proof: somebody with two characters who logs into the
     * other one has not stopped owning the first.
     *
     * @return bool whether the reported character is the account's name
     */
    public function proveFromPlugin(User $user, string $rsn): bool
    {
        if (! RuneliteName::sameRsn($rsn, $user->osrs_username)) {
            return false;
        }

        $user->forceFill([
            'osrs_proven_at' => Carbon::now(),
            'osrs_proven_via' => 'runelite',
        ])->save();

        return true;
    }

    /**
     * Re-check the name already on the account — the "check again" action
     * behind the unconfirmed notice.
     */
    public function recheck(User $user): ?bool
    {
        if (blank($user->osrs_username)) {
            return null;
        }

        return $this->apply($user, $user->osrs_username);
    }
}
