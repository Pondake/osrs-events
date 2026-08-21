<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The create → read-back → update round trip, field by field.
 *
 * Every previous test of this covered one field at a time, or asserted the
 * request succeeded rather than that the value survived. That is how a date
 * shipped that saved correctly, came back in a shape the date input cannot
 * display, and then read as "no date set" — a 200 the whole way through.
 *
 * So the read-back is asserted here as its own step: what the edit form is
 * handed has to be usable by the controls it feeds, not merely present.
 */
class EventEditFlowTest extends TestCase
{
    use RefreshDatabase;

    private function author(): User
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $user->assignRole($role);

        return $user;
    }

    /** Every field the create form can submit for a Snakes & Ladders board. */
    private function payload(array $overrides = []): array
    {
        $fields = [
            'title' => 'Clan Skirmish',
            'type' => 'SNAKES_LADDERS',
            'description' => 'A test board.',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'size' => 'SIZE_7X7',
            'dice_roll_limit' => 3,
            ...$overrides,
        ];

        // A race has no grid, and `size` is `sometimes|in:…` — a key that is
        // PRESENT and null fails that, where an absent one is skipped. So an
        // override of null means "this type does not have that field", not
        // "send null".
        return array_filter($fields, fn ($value) => $value !== null);
    }

    private function createEvent(User $user, array $overrides = []): Event
    {
        Http::fake();

        $this->actingAs($user)->post('/events', $this->payload($overrides))->assertRedirect();

        return Event::firstOrFail();
    }

    #[Test]
    public function creating_persists_every_field_it_was_given(): void
    {
        $event = $this->createEvent($this->author());
        $board = Board::where('event_id', $event->id)->firstOrFail();

        $this->assertSame('Clan Skirmish', $event->title);
        $this->assertSame('SNAKES_LADDERS', $event->type);
        $this->assertSame('A test board.', $event->description);
        $this->assertSame('SOLO', $event->mode);
        $this->assertSame('OPEN', $event->access_mode);
        $this->assertTrue($event->is_listed);
        $this->assertSame('2026-09-01', $event->start_date->toDateString());
        $this->assertSame('2026-09-30', $event->end_date->toDateString());
        $this->assertSame('SIZE_7X7', $board->size);
        $this->assertSame(3, $board->dice_roll_limit);
    }

    /**
     * The bug this class exists for. The dates are stored correctly and come
     * back as full ISO timestamps, which `<input type="date">` refuses — so
     * the edit form showed them blank, and saving that blank wiped them.
     */
    #[Test]
    public function the_edit_payload_carries_dates_a_date_input_can_actually_show(): void
    {
        $user = $this->author();
        $event = $this->createEvent($user);

        $props = $this->actingAs($user)
            ->get("/events/{$event->id}")
            ->viewData('page')['props'];

        $start = $props['board']['start_date'] ?? $props['event']['start_date'] ?? null;

        $this->assertNotNull($start, 'the edit form is handed no start date at all');

        // Whatever the wire format, the first ten characters have to be the
        // calendar day — that is the entire contract with the date input.
        $this->assertSame(
            '2026-09-01',
            substr((string) $start, 0, 10),
            'the date reaches the form in a shape its input cannot display',
        );
    }

    #[Test]
    public function updating_changes_every_field_and_leaves_nothing_behind(): void
    {
        $user = $this->author();
        $event = $this->createEvent($user);

        $this->actingAs($user)->patch("/events/{$event->id}", [
            'title' => 'Renamed Skirmish',
            'description' => 'Edited.',
            'mode' => 'TEAM',
            'access_mode' => 'INVITE',
            'is_listed' => false,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-20',
            'size' => 'SIZE_9X9',
            'dice_roll_limit' => 5,
        ])->assertRedirect();

        $event->refresh();
        $board = Board::where('event_id', $event->id)->firstOrFail();

        $this->assertSame('Renamed Skirmish', $event->title);
        $this->assertSame('Edited.', $event->description);
        $this->assertSame('TEAM', $event->mode);
        $this->assertSame('INVITE', $event->access_mode);
        $this->assertFalse($event->is_listed);
        $this->assertSame('2026-10-05', $event->start_date->toDateString());
        $this->assertSame('2026-10-20', $event->end_date->toDateString());
        $this->assertSame('SIZE_9X9', $board->size);
        $this->assertSame(5, $board->dice_roll_limit);
    }

    /**
     * null is the stored form of "unlimited", and the form now has a control
     * that produces it. It has to survive the round trip, or the toggle is
     * decorative.
     */
    #[Test]
    public function unlimited_dice_rolls_round_trip_as_null(): void
    {
        $user = $this->author();
        $event = $this->createEvent($user);

        $this->actingAs($user)
            ->patch("/events/{$event->id}", ['dice_roll_limit' => null])
            ->assertRedirect();

        $this->assertNull(Board::where('event_id', $event->id)->firstOrFail()->dice_roll_limit);
    }

    /**
     * Dates are nullable — an open-ended board is a real thing here — so
     * clearing them has to be possible and has to stick.
     */
    #[Test]
    public function dates_can_be_cleared_back_to_open_ended(): void
    {
        $user = $this->author();
        $event = $this->createEvent($user);

        $this->actingAs($user)->patch("/events/{$event->id}", [
            'start_date' => null,
            'end_date' => null,
        ])->assertRedirect();

        $event->refresh();

        $this->assertNull($event->start_date);
        $this->assertNull($event->end_date);
    }

    /** Editing must never cost the creator their ownership. */
    #[Test]
    public function editing_leaves_the_owner_in_place(): void
    {
        $user = $this->author();
        $event = $this->createEvent($user);
        $other = User::factory()->create();

        $this->actingAs($user)->patch("/events/{$event->id}", [
            'title' => 'Still Mine',
            'author_ids' => [$other->id],
        ])->assertRedirect();

        $this->assertTrue(
            BoardAuthor::where(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true])->exists(),
            'the owner row was removed by a plain edit',
        );
        $this->assertTrue($user->fresh()->canEditEvent($event->fresh()));
    }

    /**
     * The detail page renders its edit button on `canEdit`, so the button
     * being missing and the prop being false are the same bug seen from two
     * ends. Asserted per event type because a skill race and a board are
     * different pages with different templates, and only one of them used to
     * render anything at all for this.
     */
    #[Test]
    public function the_author_is_told_they_can_edit_on_every_event_page(): void
    {
        $user = $this->author();

        $board = $this->createEvent($user);

        $this->assertTrue(
            $this->actingAs($user)->get("/events/{$board->id}")->viewData('page')['props']['canEdit'],
            'a board author is not offered editing on the board page',
        );

        Event::query()->delete();

        $race = $this->createEvent($user, [
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'size' => null,
            'dice_roll_limit' => null,
        ]);

        $this->assertTrue(
            $this->actingAs($user)->get("/events/{$race->id}")->viewData('page')['props']['canEdit'],
            'a race author is not offered editing on the race page',
        );
    }

    #[Test]
    public function someone_who_is_not_an_author_cannot_edit(): void
    {
        $event = $this->createEvent($this->author());

        $stranger = User::factory()->create(['osrs_username' => 'Zezima']);

        $this->actingAs($stranger)
            ->patch("/events/{$event->id}", ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Clan Skirmish', $event->fresh()->title);
    }

    /** A metric race has no board, so its own fields have to round trip too. */
    #[Test]
    public function a_skill_race_keeps_its_metric_and_dates_through_an_edit(): void
    {
        $user = $this->author();

        $event = $this->createEvent($user, [
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'size' => null,
            'dice_roll_limit' => null,
        ]);

        $this->actingAs($user)->patch("/events/{$event->id}", [
            'metric' => 'woodcutting',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ])->assertRedirect();

        $event->refresh();

        $this->assertSame('woodcutting', $event->metric);
        $this->assertSame('2026-11-01', $event->start_date->toDateString());
        $this->assertSame('2026-11-30', $event->end_date->toDateString());
    }
}
