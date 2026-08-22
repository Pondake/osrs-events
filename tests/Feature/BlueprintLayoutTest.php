<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A template that brings the board with it.
 *
 * Settings are the easy half of reusing a format — a grid size and a win
 * condition are three clicks. The evening a host actually spends is deciding
 * which task sits on which tile and where the snakes run, and until now that
 * was thrown away every time.
 *
 * The layout is a snapshot, like the settings: read once when the template is
 * saved. What makes it more delicate than the settings is that it outlives
 * the things it points at — a Task can be renamed or deleted, and the next
 * event might not even be the same size.
 */
class BlueprintLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function host(?Event $event = null): User
    {
        $user = User::factory()->create(['osrs_username' => 'Host', 'nickname' => 'Host']);

        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $user->assignRole($role);

        if ($event !== null) {
            BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true]);
        }

        return $user;
    }

    /** A 5x5 board with a task, a snake and a ladder on it. */
    private function board(): array
    {
        $event = Event::create([
            'title' => 'Weekend Warmup',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_5X5', 'dice_roll_limit' => 2]);

        $task = Task::create(['title' => 'Kill 50 cows']);

        Tile::create(['board_id' => $event->board->id, 'position' => 3, 'type' => 'NORMAL', 'task_id' => $task->id]);
        Tile::create(['board_id' => $event->board->id, 'position' => 8, 'type' => 'LADDER', 'target_position' => 18]);
        Tile::create(['board_id' => $event->board->id, 'position' => 20, 'type' => 'SNAKE', 'target_position' => 6]);
        Tile::create(['board_id' => $event->board->id, 'position' => 11, 'type' => 'NORMAL', 'title_override' => 'Host says hello']);

        return [$event->fresh(), $task];
    }

    private function saveAs(User $host, Event $event, string $title): EventBlueprint
    {
        $this->actingAs($host)
            ->postJson("/events/{$event->id}/blueprint", ['title' => $title])
            ->assertCreated();

        return EventBlueprint::where('title', $title)->firstOrFail();
    }

    // -------------------------------------------------------------- capture

    #[Test]
    public function saving_a_board_captures_every_tile_on_it(): void
    {
        [$event, $task] = $this->board();
        $blueprint = $this->saveAs($this->host($event), $event, 'Weekend format');

        $this->assertTrue($blueprint->hasLayout());
        $this->assertCount(4, $blueprint->layout);

        $byPosition = collect($blueprint->layout)->keyBy('position');

        $this->assertSame($task->id, $byPosition[3]['task_id']);
        $this->assertSame('Kill 50 cows', $byPosition[3]['title']);
        $this->assertSame('LADDER', $byPosition[8]['type']);
        $this->assertSame(18, $byPosition[8]['target_position']);
        $this->assertSame('SNAKE', $byPosition[20]['type']);
        $this->assertSame('Host says hello', $byPosition[11]['title']);
    }

    /**
     * The id keeps the tile linked to the shared Task; the title survives the
     * Task being renamed or deleted. A year-old blueprint that says nothing
     * is not a blueprint.
     */
    #[Test]
    public function it_stores_the_task_id_and_the_title_it_had(): void
    {
        [$event] = $this->board();
        $blueprint = $this->saveAs($this->host($event), $event, 'Weekend format');

        $entry = collect($blueprint->layout)->firstWhere('position', 3);

        $this->assertNotNull($entry['task_id']);
        $this->assertSame('Kill 50 cows', $entry['title']);
    }

    #[Test]
    public function a_bingo_card_captures_its_filled_squares(): void
    {
        $event = Event::create([
            'title' => 'Clan Bingo',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $card = $event->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);

        $card->squares()->where('position', 0)->update(['title_override' => 'Kill Zulrah', 'points' => 3]);
        $card->squares()->where('position', 4)->update(['is_wildcard' => true]);

        $blueprint = $this->saveAs($this->host($event), $event->fresh(), 'Bingo format');

        // The seven squares nobody filled in carry nothing worth saving.
        $this->assertCount(2, $blueprint->layout);

        $byPosition = collect($blueprint->layout)->keyBy('position');

        $this->assertSame('Kill Zulrah', $byPosition[0]['title']);
        $this->assertSame(3, $byPosition[0]['points']);
        $this->assertTrue($byPosition[4]['is_wildcard']);
    }

    /** An empty board is a grid size, and that is already in the settings. */
    #[Test]
    public function an_event_with_nothing_on_its_board_saves_no_layout(): void
    {
        $event = Event::create([
            'title' => 'Not built yet',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_5X5']);

        $blueprint = $this->saveAs($this->host($event->fresh()), $event, 'Empty format');

        $this->assertFalse($blueprint->hasLayout());
    }

    // ---------------------------------------------------------------- apply

    #[Test]
    public function creating_an_event_from_a_template_lays_out_its_board(): void
    {
        [$event, $task] = $this->board();
        $host = $this->host($event);
        $blueprint = $this->saveAs($host, $event, 'Weekend format');

        $this->actingAs($host)->post('/events', [
            'title' => 'This month',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $made = Event::where('title', 'This month')->firstOrFail();
        $tiles = $made->board->tiles()->orderBy('position')->get()->keyBy('position');

        $this->assertCount(4, $tiles);
        $this->assertSame($task->id, $tiles[3]->task_id);
        $this->assertSame('LADDER', $tiles[8]->type);
        $this->assertSame(18, $tiles[8]->target_position);
        $this->assertSame('Host says hello', $tiles[11]->title_override);
    }

    #[Test]
    public function a_bingo_card_is_laid_out_too(): void
    {
        $source = Event::create([
            'title' => 'Clan Bingo',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $card = $source->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);
        $card->squares()->where('position', 0)->update(['title_override' => 'Kill Zulrah', 'points' => 3]);

        $host = $this->host($source);
        $blueprint = $this->saveAs($host, $source->fresh(), 'Bingo format');

        $this->actingAs($host)->post('/events', [
            'title' => 'Bingo again',
            'type' => 'BINGO',
            'bingo_size' => 3,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $made = Event::where('title', 'Bingo again')->firstOrFail();
        $square = $made->bingoCard->squares()->where('position', 0)->firstOrFail();

        $this->assertSame('Kill Zulrah', $square->title_override);
        $this->assertSame(3, $square->points);
        // And the rest of the grid still exists, unfilled.
        $this->assertSame(9, $made->bingoCard->squares()->count());
    }

    /**
     * A layout is tied to the size it was saved at, and the picker filters on
     * that — but the size can still be changed on the Format step after a
     * template is applied. Silently stacking three tiles onto the last square
     * would be worse than leaving them out.
     */
    #[Test]
    public function tiles_that_fall_off_a_smaller_board_are_dropped_not_clamped(): void
    {
        [$event] = $this->board();
        $host = $this->host($event);
        $blueprint = $this->saveAs($host, $event, 'Weekend format');

        // 5x5 layout (positions up to 20) poured into a board with 25 tiles
        // is fine; the same layout on a grid of 9 is not.
        $this->actingAs($host)->post('/events', [
            'title' => 'Too small',
            'type' => 'BINGO',
            'bingo_size' => 3,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $made = Event::where('title', 'Too small')->firstOrFail();

        // Nothing landed beyond the last square, and nothing stacked on it.
        $this->assertSame(9, $made->bingoCard->squares()->count());
        $this->assertSame(0, $made->bingoCard->squares()->where('position', '>', 8)->count());
    }

    /** A snake pointing off the end of a smaller board is a snake to nowhere. */
    #[Test]
    public function a_jump_that_no_longer_fits_becomes_a_plain_tile(): void
    {
        $event = Event::create([
            'title' => 'Big board',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_7X7']);
        Tile::create(['board_id' => $event->board->id, 'position' => 2, 'type' => 'LADDER', 'target_position' => 40]);

        $host = $this->host($event->fresh());
        $blueprint = $this->saveAs($host, $event->fresh(), 'Big format');

        $this->actingAs($host)->post('/events', [
            'title' => 'Small board',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $tile = Event::where('title', 'Small board')->firstOrFail()
            ->board->tiles()->where('position', 2)->firstOrFail();

        $this->assertNull($tile->target_position);
    }

    /**
     * A blueprint outlives the tasks it points at. The tile still has to say
     * what it asks for.
     */
    #[Test]
    public function a_deleted_task_leaves_its_title_behind(): void
    {
        [$event, $task] = $this->board();
        $host = $this->host($event);
        $blueprint = $this->saveAs($host, $event, 'Weekend format');

        $task->delete();

        $this->actingAs($host)->post('/events', [
            'title' => 'After the purge',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $tile = Event::where('title', 'After the purge')->firstOrFail()
            ->board->tiles()->where('position', 3)->firstOrFail();

        $this->assertNull($tile->task_id);
        $this->assertSame('Kill 50 cows', $tile->title_override);
    }

    // --------------------------------------------------------------- access

    /**
     * The id comes from the browser, so it is a claim. Without the visibility
     * scope, guessing one would be a way to read another clan's board.
     */
    #[Test]
    public function another_clans_layout_cannot_be_pulled_in_by_id(): void
    {
        [$event] = $this->board();
        $theirHost = $this->host($event);

        $blueprint = $this->saveAs($theirHost, $event, 'Their format');
        $blueprint->update(['guild_id' => '999999999999999999']);

        $outsider = $this->host();

        $this->actingAs($outsider)->post('/events', [
            'title' => 'Borrowed',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => $blueprint->id,
        ])->assertRedirect();

        $made = Event::where('title', 'Borrowed')->firstOrFail();

        $this->assertSame(0, $made->board->tiles()->count());
    }

    #[Test]
    public function an_unknown_blueprint_id_is_refused_rather_than_ignored(): void
    {
        $host = $this->host();

        $this->actingAs($host)->post('/events', [
            'title' => 'Nonsense',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'blueprint_id' => '01a00000-0000-7000-8000-000000000000',
        ])->assertSessionHasErrors('blueprint_id');
    }

    /** Creating without one still works, which is most of the time. */
    #[Test]
    public function an_event_can_still_be_made_from_nothing(): void
    {
        $host = $this->host();

        $this->actingAs($host)->post('/events', [
            'title' => 'From scratch',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
        ])->assertRedirect();

        $this->assertSame(0, Event::where('title', 'From scratch')->firstOrFail()->board->tiles()->count());
    }

    // ------------------------------------------------------------ the picker

    #[Test]
    public function the_picker_says_whether_a_format_brings_a_board(): void
    {
        [$event] = $this->board();
        $host = $this->host($event);
        $this->saveAs($host, $event, 'Weekend format');

        $blueprints = $this->actingAs($host)->getJson('/event-blueprints')->json('blueprints');

        $this->assertSame(4, collect($blueprints)->firstWhere('title', 'Weekend format')['layoutCount']);
    }

    /**
     * Yours first, then your clan's, then the set that ships with the app.
     *
     * This was twenty rows sorted by title, which is right for an
     * autocomplete and wrong for a gallery: a format saved as "Weekend
     * format" never appeared at all once the seeded set filled the first
     * twenty. Found by saving one and not being able to find it.
     */
    #[Test]
    public function your_own_formats_come_first(): void
    {
        [$event] = $this->board();
        $host = $this->host($event);

        EventBlueprint::create(['title' => 'AAA ships with the app', 'type' => 'BINGO', 'is_active' => true]);
        $this->saveAs($host, $event, 'ZZZ mine');

        $titles = array_column(
            $this->actingAs($host)->getJson('/event-blueprints')->json('blueprints'),
            'title',
        );

        // Alphabetically last, first in the list.
        $this->assertSame('ZZZ mine', $titles[0]);
        $this->assertContains('AAA ships with the app', $titles);
    }

    /** The rows themselves stay on the server; the picker only needs a count. */
    #[Test]
    public function the_picker_does_not_ship_the_whole_layout(): void
    {
        [$event] = $this->board();
        $host = $this->host($event);
        $this->saveAs($host, $event, 'Weekend format');

        $entry = collect($this->actingAs($host)->getJson('/event-blueprints')->json('blueprints'))
            ->firstWhere('title', 'Weekend format');

        $this->assertArrayNotHasKey('layout', $entry);
    }
}
