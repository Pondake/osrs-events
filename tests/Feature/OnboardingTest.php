<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The first-run flow's server side.
 *
 * Small, but `joinableBoards` has already shipped a production-only 500 once
 * and the shape of that bug is worth guarding: it selected a column that had
 * moved to another table, and SQLite reads an unknown identifier in a SELECT
 * list as a string literal rather than raising. So dev looked fine and
 * PostgreSQL 500'd. A test asserting the VALUE — not just that a key exists
 * — catches it here, because the literal comes back as the word "size".
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['osrs_username' => 'Pondake']);
    }

    private function event(array $attributes = [], bool $withBoard = true): Event
    {
        $event = Event::create(array_merge([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ], $attributes));

        if ($withBoard) {
            $event->board()->create(['size' => 'SIZE_5X5']);
        }

        BoardAuthor::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['osrs_username' => 'Host'])->id,
            'is_owner' => true,
        ]);

        return $event->fresh();
    }

    // ------------------------------------------------------------- flow state

    #[Test]
    public function finishing_the_flow_is_recorded_so_it_stops_reappearing(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/onboarding/complete')->assertRedirect();

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    #[Test]
    public function it_can_be_asked_for_again(): void
    {
        $user = $this->user();
        $user->update(['onboarding_completed_at' => now()]);

        $this->actingAs($user)->post('/onboarding/reset')->assertRedirect();

        $this->assertNull($user->fresh()->onboarding_completed_at);
    }

    #[Test]
    public function none_of_it_is_reachable_signed_out(): void
    {
        $this->post('/onboarding/complete')->assertRedirect('/login');
        $this->get('/onboarding/joinable-boards')->assertRedirect('/login');
    }

    // -------------------------------------------------------- joinable boards

    #[Test]
    public function an_open_board_is_offered(): void
    {
        $event = $this->event(['title' => 'Anyone welcome']);

        $boards = $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards');

        $this->assertSame(['Anyone welcome'], array_column($boards, 'title'));
        $this->assertSame($event->id, $boards[0]['id']);
    }

    /**
     * The size comes from the board, which is a different table since the
     * split. Asserting the value rather than the key is the point — a name
     * SQLite does not recognise comes back as the literal string.
     */
    #[Test]
    public function each_board_carries_its_real_size(): void
    {
        $this->event();

        $boards = $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards');

        $this->assertSame('SIZE_5X5', $boards[0]['size']);
        $this->assertArrayHasKey($boards[0]['size'], Board::TILE_COUNTS);
    }

    /** A race or a bingo card has no board, so it has no size to report. */
    #[Test]
    public function an_event_with_no_board_reports_no_size_rather_than_erroring(): void
    {
        $this->event(['type' => 'BINGO'], withBoard: false);

        $boards = $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards');

        $this->assertNull($boards[0]['size']);
    }

    /** Listing something that needs a code the user does not have is a wall. */
    #[Test]
    public function an_invite_only_board_is_not_offered(): void
    {
        $this->event(['access_mode' => 'INVITE']);

        $this->assertSame([], $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards'));
    }

    #[Test]
    public function an_unlisted_board_is_not_offered(): void
    {
        $this->event(['is_listed' => false]);

        $this->assertSame([], $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards'));
    }

    #[Test]
    public function a_guild_board_is_offered_only_to_that_guild(): void
    {
        $this->event(['access_mode' => 'GUILD', 'required_guild_id' => '111']);

        $outsider = $this->user();
        $this->assertSame([], $this->actingAs($outsider)
            ->getJson('/onboarding/joinable-boards')
            ->json('boards'));

        $member = $this->user();
        UserGuild::create(['user_id' => $member->id, 'guild_id' => '111', 'guild_name' => 'My Clan']);

        $this->assertCount(1, $this->actingAs($member)
            ->getJson('/onboarding/joinable-boards')
            ->json('boards'));
    }

    /** Four at most — the step is a nudge, not a directory. */
    #[Test]
    public function it_offers_a_handful_rather_than_everything(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->event(['title' => "Board {$i}"]);
        }

        $this->assertCount(4, $this->actingAs($this->user())
            ->getJson('/onboarding/joinable-boards')
            ->json('boards'));
    }
}
