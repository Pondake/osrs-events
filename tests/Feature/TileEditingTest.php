<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editing the tiles of a Snakes & Ladders board.
 *
 * A tile is identified by (board_id, position) rather than by a pre-existing
 * row — the grid is not created up front, and clicking an empty square is
 * what brings its tile into being. That makes the endpoint an upsert taking
 * a position from the browser, which is a shape worth checking carefully:
 * the position has to be on the board, and the board has to exist.
 */
class TileEditingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event} */
    private function board(string $size = 'SIZE_5X5'): array
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => $size]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        return [$owner, $event->fresh()];
    }

    // ------------------------------------------------------------- the happy path

    #[Test]
    public function an_author_can_put_a_task_on_a_tile(): void
    {
        [$owner, $event] = $this->board();
        $task = Task::create(['title' => 'Kill 50 cows']);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles", [
            'position' => 3,
            'task_id' => $task->id,
            'type' => 'NORMAL',
        ])->assertRedirect();

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->assertSame($task->id, $tile->task_id);
    }

    /** Editing the same position twice updates rather than duplicating. */
    #[Test]
    public function saving_the_same_position_twice_keeps_one_tile(): void
    {
        [$owner, $event] = $this->board();

        foreach (['First', 'Second'] as $title) {
            $this->actingAs($owner)->post("/events/{$event->id}/tiles", [
                'position' => 3,
                'title_override' => $title,
                'type' => 'NORMAL',
            ])->assertRedirect();
        }

        $tiles = Tile::where('board_id', $event->board->id)->where('position', 3)->get();

        $this->assertCount(1, $tiles);
        $this->assertSame('Second', $tiles->first()->title_override);
    }

    #[Test]
    public function an_author_can_clear_a_tile(): void
    {
        [$owner, $event] = $this->board();

        $this->actingAs($owner)->post("/events/{$event->id}/tiles", ['position' => 3, 'type' => 'NORMAL']);
        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)->delete("/events/{$event->id}/tiles/{$tile->id}")->assertRedirect();

        $this->assertNull(Tile::find($tile->id));
    }

    // -------------------------------------------------------------- permissions

    #[Test]
    public function somebody_who_cannot_edit_the_event_cannot_touch_its_tiles(): void
    {
        [, $event] = $this->board();
        $outsider = User::factory()->create(['osrs_username' => 'Nosy']);

        $this->actingAs($outsider)
            ->post("/events/{$event->id}/tiles", ['position' => 3, 'type' => 'NORMAL'])
            ->assertForbidden();
    }

    /**
     * A tile belongs to one board. Passing another event's tile id to this
     * event's delete route must not work — the same class of mix-up as
     * comparing a board id to an event id, which has bitten this codebase
     * before.
     */
    #[Test]
    public function a_tile_from_another_board_cannot_be_deleted_through_this_one(): void
    {
        [$owner, $mine] = $this->board();
        [, $theirs] = $this->board();

        $this->actingAs($owner)->post("/events/{$mine->id}/tiles", ['position' => 1, 'type' => 'NORMAL']);

        $strayTile = Tile::create([
            'board_id' => $theirs->board->id,
            'position' => 1,
            'type' => 'NORMAL',
        ]);

        $this->actingAs($owner)
            ->delete("/events/{$mine->id}/tiles/{$strayTile->id}")
            ->assertNotFound();

        $this->assertNotNull(Tile::find($strayTile->id));
    }

    // ------------------------------------------------------------- the bounds

    /**
     * A 5x5 board has 25 tiles. Position 99 is not one of them, and a row
     * there is an orphan: it renders nowhere, and it counts in every query
     * that asks how many tiles a board has.
     */
    #[Test]
    public function a_position_off_the_end_of_the_board_is_refused(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles", ['position' => 99, 'type' => 'NORMAL'])
            ->assertSessionHasErrors('position');

        $this->assertSame(0, Tile::where('board_id', $event->board->id)->count());
    }

    #[Test]
    public function the_last_position_on_the_board_is_accepted(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles", ['position' => 24, 'type' => 'NORMAL'])
            ->assertSessionHasNoErrors();
    }

    /** A snake pointing off the board sends a player nowhere. */
    #[Test]
    public function a_target_off_the_end_of_the_board_is_refused(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');

        $this->actingAs($owner)->post("/events/{$event->id}/tiles", [
            'position' => 3,
            'type' => 'SNAKE',
            'target_position' => 99,
        ])->assertSessionHasErrors('target_position');
    }

    /**
     * An event with no board at all — a bingo card or a race. The tile
     * endpoint identifies its row by (board_id, position), so a null board
     * would write a tile belonging to no board rather than refusing.
     */
    #[Test]
    public function an_event_with_no_board_has_no_tiles_to_edit(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles", ['position' => 0, 'type' => 'NORMAL'])
            ->assertNotFound();

        $this->assertSame(0, Tile::whereNull('board_id')->count());
    }
}
