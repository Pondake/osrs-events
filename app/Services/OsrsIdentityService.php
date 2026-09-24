<?php

namespace App\Services;

use App\Models\OsrsAccount;
use App\Models\Setting;
use App\Models\User;
use App\Support\RuneliteName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * Every character on another account with this name, compared the way
     * the game does. Alts count exactly like mains.
     */
    private function sameRsnQuery(?User $user, string $username): Builder
    {
        $normalised = mb_strtolower(trim(preg_replace('/[\s_\-]+/u', ' ', $username)));

        $query = OsrsAccount::query()
            ->whereRaw("lower(replace(replace(username, '_', ' '), '-', ' ')) = ?", [$normalised]);

        if ($normalised === '') {
            // Matches nothing rather than everything — a blank name is not a
            // claim on anything.
            $query->whereRaw('1 = 0');
        }

        if ($user !== null) {
            $query->where('user_id', '!=', $user->id);
        }

        return $query;
    }

    /** How many characters an account may hold, main included. */
    public static function maxCharacters(): int
    {
        return max(1, (int) Setting::get('max_osrs_characters'));
    }

    /**
     * Replace the account's characters with this list, main first.
     *
     * A name already on the account keeps its row, and with it its proof —
     * matched the way the game compares names, so re-saving "iron_sample"
     * does not reset "Iron Sample". Anything else is a new character: it is
     * looked up on Wise Old Man and starts unproven. A row left out is
     * removed; standings and claims keep the name it played under.
     *
     * Validation (shape, proof by another account, the limit) is the
     * caller's; see OsrsCharacters.
     *
     * @param  array<int, string>  $names
     * @return array{missing: array<int, string>, taken: array<int, string>} new names Wise Old Man did not know, and new names another account also carries
     */
    public function saveCharacters(User $user, array $names): array
    {
        $names = array_values(array_filter(array_map(fn ($name) => trim((string) $name), $names), fn ($name) => $name !== ''));
        $existing = $user->osrsAccounts()->get();
        $plan = [];
        $missing = [];
        $taken = [];

        foreach ($names as $name) {
            $row = $existing->first(fn (OsrsAccount $account) => RuneliteName::sameRsn($account->username, $name)
                && ! collect($plan)->contains(fn ($step) => $step['row']?->is($account)));

            if ($row !== null) {
                $plan[] = ['row' => $row, 'name' => $row->username, 'verified' => $row->osrs_verified_at];

                continue;
            }

            // Outside the transaction: it is somebody else's API.
            $result = $this->wom->findPlayer($name);

            if ($result['found'] === true && filled($result['displayName'])) {
                $name = $result['displayName'];
            }

            if ($result['found'] === false) {
                $missing[] = $name;
            }

            if ($this->takenByAnother($user, $name)) {
                $taken[] = $name;
            }

            $plan[] = ['row' => null, 'name' => $name, 'verified' => $result['found'] === true ? Carbon::now() : null];
        }

        DB::transaction(function () use ($user, $existing, $plan) {
            $kept = collect($plan)->pluck('row')->filter();

            $existing->reject(fn (OsrsAccount $account) => $kept->contains(fn ($row) => $row->is($account)))
                ->each->delete();

            // Alts first, so that row 0 is free for the main by the time the
            // columns below are written and User::booted() syncs it.
            foreach (array_slice($plan, 1, preserve_keys: true) as $position => $step) {
                if ($step['row'] !== null) {
                    $step['row']->forceFill(['position' => $position])->save();

                    continue;
                }

                OsrsAccount::create([
                    'user_id' => $user->id,
                    'username' => $step['name'],
                    'position' => $position,
                    'osrs_verified_at' => $step['verified'],
                ]);
            }

            $main = $plan[0] ?? null;

            if ($main === null) {
                return;
            }

            $main['row']?->forceFill(['position' => 0])->save();

            $user->forceFill([
                'osrs_username' => $main['name'],
                'osrs_verified_at' => $main['verified'],
                'osrs_proven_at' => $main['row']?->osrs_proven_at,
                'osrs_proven_via' => $main['row']?->osrs_proven_via,
            ])->save();
        });

        return ['missing' => $missing, 'taken' => $taken];
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
        // "Main Sample" beats "main sample" or "MAIN SAMPLE".
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

        // A main renamed to one of the account's alts: that row was the same
        // character, and one character is one row.
        $user->osrsAccounts()->where('position', '>', 0)->get()
            ->filter(fn (OsrsAccount $alt) => RuneliteName::sameRsn($alt->username, $username))
            ->each->delete();

        return $result['found'];
    }

    /**
     * Record that a RuneLite client reported this account logged in as a
     * character.
     *
     * One of the account's own characters is proved. An unknown one is added
     * as a proved alt, unless the plugin asked not to (`$addAlt`), the
     * account is full, or another account has already proved the name. A
     * mismatch never clears an older proof: logging into your other
     * character does not stop you owning the first.
     *
     * @return array{matched: bool, added: bool, reason: ?string}
     */
    public function proveFromPlugin(User $user, string $rsn, bool $addAlt = true): array
    {
        $rsn = trim($rsn);
        $row = $user->osrsAccounts()->get()->first(fn (OsrsAccount $account) => RuneliteName::sameRsn($account->username, $rsn));
        $proof = ['osrs_proven_at' => Carbon::now(), 'osrs_proven_via' => 'runelite'];

        if ($row !== null) {
            // The main is written through the account so the mirror follows.
            $row->isMain() ? $user->forceFill($proof)->save() : $row->forceFill($proof)->save();

            return ['matched' => true, 'added' => false, 'reason' => null];
        }

        $reason = match (true) {
            ! $addAlt => 'disabled',
            $user->osrsAccounts()->count() >= self::maxCharacters() => 'limit',
            $this->provenByAnother($user, $rsn) => 'taken',
            default => null,
        };

        if ($reason !== null) {
            return ['matched' => false, 'added' => false, 'reason' => $reason];
        }

        if (blank($user->osrs_username)) {
            $user->forceFill(['osrs_username' => $rsn, ...$proof])->save();
        } else {
            OsrsAccount::create([
                'user_id' => $user->id,
                'username' => $rsn,
                'position' => (int) $user->osrsAccounts()->max('position') + 1,
                ...$proof,
            ]);
        }

        return ['matched' => true, 'added' => true, 'reason' => null];
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
