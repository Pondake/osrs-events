<?php

namespace App\Services;

use App\Models\Board;
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
    public function find(Board $board, User $user): ?PlayerBoard
    {
        if ($board->mode === 'TEAM') {
            $teamId = $this->teamIdFor($board, $user);
            if ($teamId === null) {
                return null;
            }

            return PlayerBoard::where('board_id', $board->id)->where('team_id', $teamId)->first();
        }

        return PlayerBoard::where('board_id', $board->id)->where('user_id', $user->id)->first();
    }

    /**
     * Get-or-create. Used by mutating actions (roll, toggleTile) where a
     * fresh player's first action is what creates the row — never called
     * from a pure read.
     */
    public function getOrCreate(Board $board, User $user): ?PlayerBoard
    {
        if ($board->mode === 'TEAM') {
            $teamId = $this->teamIdFor($board, $user);
            if ($teamId === null) {
                return null;
            }

            return PlayerBoard::firstOrCreate(
                ['board_id' => $board->id, 'team_id' => $teamId],
                ['id' => (string) str()->uuid(), 'user_id' => $user->id, 'current_position' => 0],
            );
        }

        return PlayerBoard::firstOrCreate(
            ['user_id' => $user->id, 'board_id' => $board->id],
            ['id' => (string) str()->uuid(), 'current_position' => 0],
        );
    }

    /** Whether the user is eligible to have a PlayerBoard on this board at all. */
    public function hasTeam(Board $board, User $user): bool
    {
        return $board->mode !== 'TEAM' || $this->teamIdFor($board, $user) !== null;
    }

    /** Which of the board's assigned teams (if any) the user belongs to. */
    private function teamIdFor(Board $board, User $user): ?string
    {
        $boardTeam = $board->boardTeams()
            ->with('team.members')
            ->get()
            ->first(fn ($bt) => $bt->team->members->contains('user_id', $user->id));

        return $boardTeam?->team_id;
    }
}
