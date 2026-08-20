<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\User;

/**
 * Ported from the old PlayersService's getOrCreatePlayerBoard()/resolvePlayerBoard()
 * TEAM-mode branch — SOLO boards get one PlayerBoard per user (unchanged); TEAM
 * boards share a single PlayerBoard per team, resolved via whichever of the
 * board's assigned teams (BoardTeam) the user is a member of (TeamMember).
 * A user with no team on a TEAM board has no PlayerBoard at all — every method
 * here returns null in that case rather than throwing, since "no team yet" is
 * an expected state the UI shows a dedicated empty state for
 * (BoardShow.vue's no_team_title/no_team_desc), not an error.
 */
class PlayerBoardService
{
    /** Read-only lookup — never creates a row. */
    public function find(Event $event, User $user): ?PlayerBoard
    {
        // No board, no progress row. A skill race ranks on XP gained and
        // never has one.
        if ($event->board === null) {
            return null;
        }

        if ($event->mode === 'TEAM') {
            $teamId = $this->teamIdFor($event, $user);
            if ($teamId === null) {
                return null;
            }

            return PlayerBoard::where('board_id', $event->board?->id)->where('team_id', $teamId)->first();
        }

        return PlayerBoard::where('board_id', $event->board?->id)->where('user_id', $user->id)->first();
    }

    /**
     * Get-or-create. Used by mutating actions (roll, toggleTile) where a
     * fresh player's first action is what creates the row — never called
     * from a pure read.
     */
    public function getOrCreate(Event $event, User $user): ?PlayerBoard
    {
        if ($event->board === null) {
            return null;
        }

        if ($event->mode === 'TEAM') {
            $teamId = $this->teamIdFor($event, $user);
            if ($teamId === null) {
                return null;
            }

            return PlayerBoard::firstOrCreate(
                ['board_id' => $event->board?->id, 'team_id' => $teamId],
                ['id' => (string) str()->uuid(), 'user_id' => $user->id, 'current_position' => 0],
            );
        }

        return PlayerBoard::firstOrCreate(
            ['user_id' => $user->id, 'board_id' => $event->board?->id],
            ['id' => (string) str()->uuid(), 'current_position' => 0],
        );
    }

    /** Whether the user is eligible to have a PlayerBoard on this board at all. */
    public function hasTeam(Event $event, User $user): bool
    {

        return $event->mode !== 'TEAM' || $this->teamIdFor($event, $user) !== null;
    }

    /** Which of the board's assigned teams (if any) the user belongs to. */
    private function teamIdFor(Event $event, User $user): ?string
    {
        $boardTeam = $event->eventTeams()
            ->with('team.members')
            ->get()
            ->first(fn ($bt) => $bt->team->members->contains('user_id', $user->id));

        return $boardTeam?->team_id;
    }
}
