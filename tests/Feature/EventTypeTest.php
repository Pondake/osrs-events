<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An event's type decides which payload table holds it — a Snakes & Ladders
 * event has a Board with tiles and player progress, a skill race has none.
 *
 * Changing it after creation therefore cannot be a field edit. Either
 * direction leaves the event broken: a race keeps an orphaned board, and a
 * board-type event with no board renders an empty grid nobody can play. So
 * the type is fixed at creation, and these lock that down.
 */
class EventTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return tap(User::factory()->create(['osrs_username' => 'Pondake']))
            ->assignRole(Role::findOrCreate('ADMIN', 'web'));
    }

    private function boardEvent(): Event
    {
        $event = Event::create([
            'title' => 'Winter Clan Grind',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        Board::create(['event_id' => $event->id, 'size' => 'SIZE_7X7']);

        return $event;
    }

    private function race(): Event
    {
        return Event::create([
            'title' => 'Skill of the Month — Mining',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
    }

    /**
     * The person who runs the event. Was a site admin until 2026-08-22, when
     * canEditEvent() stopped granting admins a write on the public side —
     * these tests are about the type being immutable, not about who is
     * allowed to try.
     */
    private function hostOf(Event $event): User
    {
        $host = User::factory()->create(['osrs_username' => 'Host']);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        return $host;
    }

    #[Test]
    public function a_board_event_cannot_become_a_race_and_orphan_its_board(): void
    {
        $event = $this->boardEvent();

        $this->actingAs($this->hostOf($event))
            ->patch("/events/{$event->id}", ['type' => 'SKILL_RACE', 'metric' => 'mining'])
            ->assertSessionHasErrors('type');

        $this->assertSame('SNAKES_LADDERS', $event->fresh()->type);
        $this->assertNotNull($event->fresh()->board, 'the board is still attached');
    }

    #[Test]
    public function a_race_cannot_become_a_board_event_it_has_no_board_for(): void
    {
        $event = $this->race();

        $this->actingAs($this->hostOf($event))
            ->patch("/events/{$event->id}", ['type' => 'SNAKES_LADDERS', 'size' => 'SIZE_7X7'])
            ->assertSessionHasErrors('type');

        $this->assertSame('SKILL_RACE', $event->fresh()->type);
    }

    /** Re-submitting the type it already has is not a change, so it passes. */
    #[Test]
    public function submitting_the_unchanged_type_is_still_allowed(): void
    {
        $admin = $this->admin();
        $event = $this->boardEvent();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $admin->id, 'is_owner' => true]);

        $this->actingAs($admin)
            ->patch("/events/{$event->id}", ['type' => 'SNAKES_LADDERS', 'title' => 'Renamed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $event->fresh()->title);
    }

    /**
     * The state a type change would produce, reached directly: the page must
     * not fatal on it, because existing databases may already contain one.
     */
    #[Test]
    public function a_board_type_event_with_no_board_still_renders(): void
    {
        Http::fake();
        $admin = $this->admin();

        $event = Event::create([
            'title' => 'Boardless somehow',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->actingAs($admin)->get("/events/{$event->id}")->assertOk();
    }
}
