<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardAccess;
use App\Models\BoardInvite;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ported from the old NestJS AccessService + InvitesService — this is the
 * enforcement layer that was missing from the prototype: BoardAccessMode
 * (OPEN/GUILD/INVITE) tables existed from the first migration pass but
 * nothing checked them, so any logged-in user could view any board
 * regardless of its access_mode. See docs/backlog.md.
 */
class BoardAccessService
{
    /** Board authors always pass regardless of access mode. */
    public function isAuthor(User $user, Board $board): bool
    {
        return $board->authors()->where('user_id', $user->id)->exists();
    }

    /**
     * Whether the user may join the board without yet having a BoardAccess
     * record. Returns a reason string when denied, for display in the UI.
     */
    public function canJoin(User $user, Board $board): array
    {
        if ($this->isAuthor($user, $board)) {
            return ['allowed' => true];
        }

        return match ($board->access_mode) {
            'OPEN' => ['allowed' => true],
            'GUILD' => $this->canJoinGuild($user, $board),
            'INVITE' => ['allowed' => false, 'reason' => 'This board requires an invite code or link.'],
            default => ['allowed' => false, 'reason' => 'Access denied.'],
        };
    }

    private function canJoinGuild(User $user, Board $board): array
    {
        if (! $board->required_guild_id) {
            return ['allowed' => true];
        }

        $isMember = UserGuild::where(['user_id' => $user->id, 'guild_id' => $board->required_guild_id])->exists();

        return $isMember
            ? ['allowed' => true]
            : ['allowed' => false, 'reason' => 'You must be a member of the required Discord server to join this board.'];
    }

    /**
     * Whether the user already has confirmed access — true if a BoardAccess
     * record exists, they're a board author, or the board is OPEN (OPEN
     * boards never need a BoardAccess row; access is implicit).
     */
    public function hasAccess(User $user, Board $board): bool
    {
        if ($board->access_mode === 'OPEN' || $this->isAuthor($user, $board)) {
            return true;
        }

        return BoardAccess::where(['board_id' => $board->id, 'user_id' => $user->id])->exists();
    }

    /**
     * Grant access. For INVITE mode, $tokenOrCode is required and consumed
     * via useInvite() (which increments BoardInvite.use_count in the same
     * transaction — the old backend's own note on why this needs to be
     * transactional, ported here rather than left as a TODO).
     */
    public function joinBoard(User $user, Board $board, ?string $tokenOrCode = null): BoardAccess
    {
        $existing = BoardAccess::where(['board_id' => $board->id, 'user_id' => $user->id])->first();
        if ($existing) {
            return $existing;
        }

        if ($board->access_mode === 'INVITE' && $tokenOrCode) {
            return $this->useInvite($board, $tokenOrCode, $user);
        }

        if (! $this->isAuthor($user, $board)) {
            $check = $this->canJoin($user, $board);
            if (! $check['allowed']) {
                throw ValidationException::withMessages(['access' => $check['reason']]);
            }
        }

        return BoardAccess::create([
            'id' => (string) str()->uuid(),
            'board_id' => $board->id,
            'user_id' => $user->id,
            'access_mode' => $board->access_mode,
        ]);
    }

    public function useInvite(Board $board, string $tokenOrCode, User $user): BoardAccess
    {
        $existing = BoardAccess::where(['board_id' => $board->id, 'user_id' => $user->id])->first();
        if ($existing) {
            return $existing;
        }

        $normalised = strtoupper(trim($tokenOrCode));

        $invite = BoardInvite::where('board_id', $board->id)
            ->where(fn ($q) => $q->where('token', $tokenOrCode)->orWhere('short_code', $normalised))
            ->first();

        if (! $invite) {
            throw ValidationException::withMessages(['access' => 'Invite not found.']);
        }
        if ($invite->expires_at && $invite->expires_at->isPast()) {
            throw ValidationException::withMessages(['access' => 'This invite has expired.']);
        }
        if ($invite->max_uses !== null && $invite->use_count >= $invite->max_uses) {
            throw ValidationException::withMessages(['access' => 'This invite has reached its maximum uses.']);
        }

        return DB::transaction(function () use ($board, $invite, $user) {
            $access = BoardAccess::create([
                'id' => (string) str()->uuid(),
                'board_id' => $board->id,
                'user_id' => $user->id,
                'invite_id' => $invite->id,
                'access_mode' => 'INVITE',
            ]);

            $invite->increment('use_count');

            return $access;
        });
    }

    public function generateUniqueShortCode(Board $board): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = collect(range(1, 6))->map(fn () => $chars[random_int(0, strlen($chars) - 1)])->implode('');

            if (! BoardInvite::where(['board_id' => $board->id, 'short_code' => $code])->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Failed to generate unique short code after 10 attempts');
    }
}
