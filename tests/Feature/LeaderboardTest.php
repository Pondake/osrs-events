<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Snakes & Ladders leaderboard.
 *
 * A page listing named people, which makes it two things at once: a ranking
 * and a disclosure. The ranking is easy to check. The disclosure is the part
 * worth being careful about — the route is behind `auth`, but an OPEN event
 * is readable by any account, and making one costs nothing.
 */
class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Somebody signed in who is not on the board. The route is behind `auth`
     * plus `require-osrs-username`, so this is the least-privileged reader
     * that can reach the page at all.
     */
    private function reader(): User
    {
        return User::factory()->create(['osrs_username' => 'Reader']);
    }

    /** @return array{0: User, 1: Event} */
    private function board(array $event = []): array
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $created = Event::create(array_merge([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ], $event));

        $created->board()->create(['size' => 'SIZE_5X5']);
        BoardAuthor::create(['event_id' => $created->id, 'user_id' => $owner->id, 'is_owner' => true]);

        for ($position = 0; $position < $created->board->tileCount(); $position++) {
            Tile::create(['board_id' => $created->board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        return [$owner, $created->fresh()];
    }

    // -------------------------------------------------------------- the ranking

    #[Test]
    public function players_are_ranked_by_how_far_along_they_are(): void
    {
        [$owner, $event] = $this->board();
        $behind = User::factory()->create(['osrs_username' => 'Behind']);

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 4]);
        PlayerBoard::create(['user_id' => $behind->id, 'board_id' => $event->board->id, 'current_position' => 12]);

        $entries = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['entries'];

        $this->assertSame([1, 2], array_column($entries, 'rank'));
        $this->assertSame([12, 4], array_column($entries, 'currentPosition'));
    }

    #[Test]
    public function it_says_how_much_board_is_left(): void
    {
        [$owner, $event] = $this->board();

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 4]);

        $entries = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['entries'];

        // 25 tiles, so the last is position 24; four in leaves twenty.
        $this->assertSame(20, $entries[0]['tilesRemaining']);
        $this->assertSame(25, $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['totalTiles']);
    }

    /** Whether the run ahead of you is worth watching. */
    #[Test]
    public function it_flags_a_snake_or_a_ladder_still_ahead(): void
    {
        [$owner, $event] = $this->board();

        Tile::where('board_id', $event->board->id)->where('position', 20)
            ->update(['type' => 'LADDER', 'target_position' => 24]);
        Tile::where('board_id', $event->board->id)->where('position', 2)
            ->update(['type' => 'SNAKE', 'target_position' => 0]);

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 10]);

        $entry = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['entries'][0];

        $this->assertTrue($entry['pathHasLadder']);
        // Already passed, so not ahead of them any more.
        $this->assertFalse($entry['pathHasSnake']);
    }

    // ------------------------------------------------------------- what it tells

    /**
     * What the page publishes about a player, it publishes to every account
     * that can open it — and on an OPEN event that is anyone who signs up.
     *
     * It was handing the browser the whole `User` model. Only `password` and
     * `remember_token` are marked hidden, so everything else on the row went
     * with it — **including the email address**. Registering an account was
     * enough to read the email of everyone playing any open board, which is
     * the exact opposite of what the participants list was built to do.
     */
    #[Test]
    public function it_does_not_publish_a_players_email_address(): void
    {
        [$owner, $event] = $this->board();
        $owner->update(['email' => 'real.person@example.com']);

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 4]);

        $response = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard");

        $response->assertDontSee('real.person@example.com', escape: false);

        $user = $response->viewData('page')['props']['entries'][0]['user'];

        $this->assertArrayNotHasKey('email', $user);
        $this->assertArrayNotHasKey('discord_id', $user);
    }

    #[Test]
    public function it_still_says_who_the_player_is(): void
    {
        [$owner, $event] = $this->board();
        $owner->update(['discord_username' => 'pondake', 'nickname' => 'Pond']);

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 4]);

        $user = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['entries'][0]['user'];

        $this->assertSame('Pond', $user['nickname']);
        $this->assertSame('pondake', $user['discord_username']);
    }

    // ------------------------------------------------------------------- access

    #[Test]
    public function a_signed_in_stranger_cannot_read_a_private_boards_leaderboard(): void
    {
        [, $event] = $this->board(['access_mode' => 'INVITE', 'is_listed' => false]);

        $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->assertForbidden();
    }

    /**
     * Readable signed out since 2026-08-31 — a ranking is part of reading a
     * listed event, and `/events` has always advertised these to strangers.
     */
    #[Test]
    public function a_listed_boards_leaderboard_is_readable_signed_out(): void
    {
        [, $event] = $this->board();

        $this->get("/events/{$event->id}/leaderboard")->assertOk();
    }

    /** An unlisted one still is not: nothing about it was ever public. */
    #[Test]
    public function an_unlisted_boards_leaderboard_is_still_refused_signed_out(): void
    {
        [, $event] = $this->board(['is_listed' => false]);

        $this->get("/events/{$event->id}/leaderboard")->assertForbidden();
    }

    /** An empty grid must not produce a negative "tiles remaining". */
    #[Test]
    public function a_board_with_no_tiles_does_not_report_negative_progress(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $event = Event::create([
            'title' => 'Not built yet',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_5X5']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        PlayerBoard::create(['user_id' => $owner->id, 'board_id' => $event->board->id, 'current_position' => 0]);

        $entry = $this->actingAs($this->reader())->get("/events/{$event->id}/leaderboard")->viewData('page')['props']['entries'][0];

        $this->assertGreaterThanOrEqual(0, $entry['tilesRemaining']);
    }
}
