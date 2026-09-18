<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\CompletedTile;
use App\Models\PlayerBoard;
use App\Models\TargetProgress;
use App\Models\Team;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who has a square or tile, and who is on the way to it.
 *
 * The board itself only has room for three faces and your own count. This is
 * what the detail dialog opens onto: the whole list, plus the running total
 * of everyone still working on a counted target.
 *
 * Built on demand for one target rather than shipped with the page — a 9x9
 * card times every competitor is a payload nobody reads, and the dialog is
 * opened one square at a time.
 *
 * **Names are gated, numbers are not.** Same rule the grid and the standings
 * already follow (see BoardAccessService::canSeeParticipants): on a listed
 * invite-only event a stranger may see that four people are three quarters of
 * the way there, and not who they are. The rows stay, the identity goes — a
 * row that disappears would understate the field, which is a different lie.
 */
class TargetDetailService
{
    /**
     * @return array{holders: array<int, array>, inProgress: array<int, array>}
     */
    public function forSquare(BingoSquare $square, bool $namesArePublic, ?string $viewerKey = null): array
    {
        $completions = BingoCompletion::where('bingo_square_id', $square->id)
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->with(['team:id,name,icon_url,guild_id,guild_icon', 'user:id,discord_username,nickname,avatar_url'])
            ->orderBy('created_at')
            ->get();

        $holders = $completions->map(fn (BingoCompletion $c) => [
            'key' => $c->team_id !== null ? "team:{$c->team_id}" : "user:{$c->user_id}",
            'name' => $c->team?->name ?? ($c->user?->nickname ?: $c->user?->discord_username),
            'avatarUrl' => $c->team?->icon_url ?? $c->team?->guild_icon_url ?? $c->user?->avatar_url,
            'status' => $c->status,
            'via' => $c->completed_via,
            'at' => $c->created_at?->toIso8601String(),
        ]);

        return [
            'holders' => self::rows($holders, $namesArePublic, $viewerKey),
            'inProgress' => self::rows(
                $this->bingoProgress($square, $holders->pluck('key')->all()),
                $namesArePublic,
                $viewerKey,
            ),
        ];
    }

    /**
     * The same two lists for a Snakes & Ladders tile.
     *
     * A tile is claimed against a player board rather than against a team or
     * a user, so the competitor here is that board — see
     * TargetProgressService::boardKey().
     *
     * @return array{holders: array<int, array>, inProgress: array<int, array>}
     */
    public function forTile(Tile $tile, bool $namesArePublic, ?string $viewerKey = null): array
    {
        $completions = CompletedTile::where('tile_id', $tile->id)
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->with([
                'playerBoard:id,user_id,team_id',
                'playerBoard.user:id,discord_username,nickname,avatar_url',
                'playerBoard.team:id,name,icon_url,guild_id,guild_icon',
            ])
            ->orderBy('completed_at')
            ->get();

        $holders = $completions->map(fn (CompletedTile $c) => [
            'key' => "board:{$c->player_board_id}",
            ...self::playerIdentity($c->playerBoard),
            'status' => $c->status,
            'via' => $c->completed_via,
            'at' => $c->completed_at?->toIso8601String(),
        ]);

        return [
            'holders' => self::rows($holders, $namesArePublic, $viewerKey),
            'inProgress' => self::rows(
                $this->tileProgress($tile, $holders->pluck('key')->all()),
                $namesArePublic,
                $viewerKey,
            ),
        ];
    }

    /**
     * Everyone with a running total on this square who has not claimed it.
     *
     * Excluding the holders is the point: a competitor who already has the
     * square would otherwise appear twice, once as done and once as "5 / 5
     * on the way", which reads as two different people.
     */
    private function bingoProgress(BingoSquare $square, array $exclude): Collection
    {
        if ($square->required_count <= 1) {
            return collect();
        }

        $totals = self::totals('bingo_square', $square->id, $exclude);

        if ($totals->isEmpty()) {
            return collect();
        }

        $teams = Team::whereIn('id', self::ids($totals, 'team'))->get(['id', 'name', 'icon_url', 'guild_id', 'guild_icon'])->keyBy('id');
        $users = User::whereIn('id', self::ids($totals, 'user'))->get(['id', 'discord_username', 'nickname', 'avatar_url'])->keyBy('id');

        return $totals->map(function (int $done, string $key) use ($teams, $users) {
            [$kind, $id] = explode(':', $key, 2);
            $team = $kind === 'team' ? $teams->get($id) : null;
            $user = $kind === 'user' ? $users->get($id) : null;

            return [
                'key' => $key,
                'name' => $team?->name ?? ($user?->nickname ?: $user?->discord_username),
                'avatarUrl' => $team?->icon_url ?? $team?->guild_icon_url ?? $user?->avatar_url,
                'done' => $done,
            ];
        })->values();
    }

    private function tileProgress(Tile $tile, array $exclude): Collection
    {
        if ($tile->required_count <= 1) {
            return collect();
        }

        $totals = self::totals('tile', $tile->id, $exclude);

        if ($totals->isEmpty()) {
            return collect();
        }

        $boards = PlayerBoard::whereIn('id', self::ids($totals, 'board'))
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url,guild_id,guild_icon'])
            ->get(['id', 'user_id', 'team_id'])
            ->keyBy('id');

        return $totals->map(function (int $done, string $key) use ($boards) {
            [, $id] = explode(':', $key, 2);

            return [
                'key' => $key,
                ...self::playerIdentity($boards->get($id)),
                'done' => $done,
            ];
        })->values();
    }

    /** @return Collection<string, int> competitor key => count */
    private static function totals(string $kind, string $targetId, array $exclude): Collection
    {
        return TargetProgress::where('kind', $kind)
            ->where('target_id', $targetId)
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('competitor_key', $exclude))
            ->selectRaw('competitor_key, count(*) as total')
            ->groupBy('competitor_key')
            ->pluck('total', 'competitor_key')
            ->map(fn ($total) => (int) $total)
            ->sortDesc();
    }

    /** @return array<int, string> the ids of one competitor kind, from "kind:id" keys */
    private static function ids(Collection $totals, string $kind): array
    {
        return $totals->keys()
            ->filter(fn (string $key) => str_starts_with($key, "{$kind}:"))
            ->map(fn (string $key) => explode(':', $key, 2)[1])
            ->values()
            ->all();
    }

    /** A team event credits the team, a solo one the player — the split competitorFor() makes. */
    private static function playerIdentity(?PlayerBoard $board): array
    {
        return [
            'name' => $board?->team?->name ?? ($board?->user?->nickname ?: $board?->user?->discord_username),
            'avatarUrl' => $board?->team?->icon_url ?? $board?->team?->guild_icon_url ?? $board?->user?->avatar_url,
        ];
    }

    /**
     * Strip the identity where it is not public, and the competitor key
     * always — it names a user or a team to anyone who can look one up.
     *
     * `isYou` is resolved here instead, because the one identity on the list
     * a reader is entitled to is their own: an anonymised list still has to
     * be able to say which row they are standing in.
     */
    private static function rows(Collection $rows, bool $namesArePublic, ?string $viewerKey): array
    {
        return $rows->map(fn (array $row) => [
            ...$row,
            'key' => null,
            'isYou' => $viewerKey !== null && $row['key'] === $viewerKey,
            'name' => $namesArePublic ? $row['name'] : null,
            'avatarUrl' => $namesArePublic ? $row['avatarUrl'] : null,
        ])->values()->all();
    }
}
