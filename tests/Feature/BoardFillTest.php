<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Fill the rest" on a Snakes & Ladders board.
 *
 * Hosts on production stopped after one to three tiles. The button has to
 * leave a board that plays — every tile asks for something, every jump
 * follows the rules — without touching what the host already set.
 */
class BoardFillTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event} */
    private function board(string $size = 'SIZE_7X7'): array
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

    private function library(int $count): void
    {
        foreach (range(1, $count) as $i) {
            Task::create(['title' => "Task {$i}"]);
        }
    }

    #[Test]
    #[DataProvider('sizes')]
    public function every_tile_ends_up_with_a_task_or_a_jump_that_follows_the_rules(string $size, int $total, int $jumps): void
    {
        [$owner, $event] = $this->board($size);
        $this->library(100);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill")->assertSessionHas('board-save');

        $tiles = Tile::where('board_id', $event->board->id)->get();
        $this->assertCount($total, $tiles);

        $normal = $tiles->where('type', 'NORMAL');
        $this->assertTrue($normal->every(fn (Tile $tile) => $tile->task_id !== null));
        $this->assertCount($normal->count(), $normal->pluck('task_id')->unique(), 'no task twice while the library lasts');

        $this->assertCount($jumps, $tiles->where('type', 'SNAKE'));
        $this->assertCount($jumps, $tiles->where('type', 'LADDER'));

        $ends = [];
        foreach ($tiles->where('type', '!=', 'NORMAL') as $jump) {
            $this->assertNull($event->board->jumpError($jump->type, $jump->position, $jump->target_position));
            $ends[] = $jump->position;
            $ends[] = $jump->target_position;
        }
        $this->assertCount(count($ends), array_unique($ends), 'no tile is part of two jumps');
    }

    public static function sizes(): array
    {
        return [
            '5x5' => ['SIZE_5X5', 25, 2],
            '7x7' => ['SIZE_7X7', 49, 4],
            '9x9' => ['SIZE_9X9', 81, 6],
        ];
    }

    #[Test]
    public function what_the_host_set_is_left_alone(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');
        $this->library(40);
        $mine = Task::create(['title' => 'My own']);

        Tile::create(['board_id' => $event->board->id, 'position' => 3, 'type' => 'NORMAL', 'task_id' => $mine->id]);
        Tile::create(['board_id' => $event->board->id, 'position' => 4, 'type' => 'NORMAL', 'title_override' => 'Wording of my own']);
        Tile::create(['board_id' => $event->board->id, 'position' => 12, 'type' => 'SNAKE', 'target_position' => 2]);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill");

        $tiles = Tile::where('board_id', $event->board->id)->get()->keyBy('position');
        $this->assertSame($mine->id, $tiles[3]->task_id);
        $this->assertNull($tiles[4]->task_id);
        $this->assertSame('Wording of my own', $tiles[4]->title_override);
        $this->assertSame(2, $tiles[12]->target_position);

        // A host who placed a jump of their own gets no extra ones.
        $this->assertSame(1, $tiles->where('type', '!=', 'NORMAL')->count());
        $this->assertSame([3], $tiles->filter(fn (Tile $tile) => $tile->task_id === $mine->id)->keys()->all());
    }

    #[Test]
    public function no_jumps_appear_once_somebody_is_playing(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');
        $this->library(40);
        PlayerBoard::create(['user_id' => User::factory()->create()->id, 'board_id' => $event->board->id, 'current_position' => 4]);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill");

        $tiles = Tile::where('board_id', $event->board->id)->get();
        $this->assertCount(25, $tiles);
        $this->assertTrue($tiles->every(fn (Tile $tile) => $tile->type === 'NORMAL' && $tile->task_id !== null));
    }

    #[Test]
    public function a_small_library_comes_round_again_rather_than_leaving_gaps(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');
        $this->library(5);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill");

        $normal = Tile::where('board_id', $event->board->id)->where('type', 'NORMAL')->get();
        $this->assertTrue($normal->every(fn (Tile $tile) => $tile->task_id !== null));
        // Spread evenly: 21 tiles over 5 tasks is 4 or 5 each.
        $this->assertLessThanOrEqual(5, $normal->countBy('task_id')->max());
    }

    #[Test]
    public function it_is_logged_so_its_use_can_be_measured(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');
        $this->library(40);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill");

        $log = AuditLog::where('action', 'board.auto_filled')->firstOrFail();
        $this->assertSame($event->id, $log->target_id);
        $this->assertSame(['tasks' => 21, 'snakes' => 2, 'ladders' => 2], $log->metadata);
    }

    #[Test]
    public function a_full_board_says_so_and_logs_nothing(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');
        $this->library(40);

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill");
        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill")
            ->assertSessionHas('board-save', trans('tile_list.fill_nothing_left'));

        $this->assertSame(1, AuditLog::where('action', 'board.auto_filled')->count());
    }

    #[Test]
    public function an_empty_library_is_reported(): void
    {
        [$owner, $event] = $this->board('SIZE_5X5');

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/fill")->assertSessionHas('board-save-error');

        $this->assertSame(0, Tile::where('board_id', $event->board->id)->count());
    }

    #[Test]
    public function somebody_who_cannot_edit_the_event_cannot_fill_it(): void
    {
        [, $event] = $this->board('SIZE_5X5');
        $this->library(40);

        $this->actingAs(User::factory()->create(['osrs_username' => 'Stranger']))
            ->post("/events/{$event->id}/tiles/fill")
            ->assertForbidden();

        $this->assertSame(0, Tile::where('board_id', $event->board->id)->count());
    }
}
