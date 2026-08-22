<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGuild;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Saving an event as a reusable format, and starting one from a saved format.
 *
 * The rule the whole feature rests on: **a copy, not a link.** The settings
 * are read once, when the template is saved. Editing the event afterwards
 * leaves the template alone, and editing the template leaves the event alone.
 * The alternative would let one host's template change under another host's
 * hands without either of them noticing.
 */
class BlueprintFromEventTest extends TestCase
{
    use RefreshDatabase;

    private const MY_GUILD = '111111111111111111';

    private function host(?Event $event = null, string $name = 'Host'): User
    {
        $user = User::factory()->create(['osrs_username' => $name, 'nickname' => $name]);

        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $user->assignRole($role);

        UserGuild::firstOrCreate(
            ['user_id' => $user->id, 'guild_id' => self::MY_GUILD],
            ['guild_name' => 'My Clan'],
        );

        if ($event !== null) {
            BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true]);
        }

        return $user;
    }

    private function bingo(): Event
    {
        $event = Event::create([
            'title' => 'Clan Bingo — Summer',
            'type' => 'BINGO',
            'mode' => 'TEAM',
            'access_mode' => 'GUILD',
            'required_guild_id' => self::MY_GUILD,
            'is_listed' => false,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $card = $event->bingoCard()->create([
            'size' => 4,
            'win_condition' => 'FULL_HOUSE',
            'line_bonus' => 3,
            'requires_approval' => true,
            'win_lines' => ['ROW', 'COLUMN'],
        ]);
        app(BingoService::class)->ensureSquares($card);

        return $event->fresh();
    }

    private function board(): Event
    {
        $event = Event::create([
            'title' => 'Weekend Warmup',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_7X7', 'dice_roll_limit' => 2]);

        return $event->fresh();
    }

    // ------------------------------------------------------------- saving

    #[Test]
    public function a_host_can_save_their_event_as_a_format(): void
    {
        $event = $this->bingo();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", ['title' => 'Clan Bingo Night'])
            ->assertCreated();

        $blueprint = EventBlueprint::where('title', 'Clan Bingo Night')->firstOrFail();

        $this->assertSame('BINGO', $blueprint->type);
        $this->assertTrue($blueprint->is_active);
    }

    /** Everything a host would otherwise fill in by hand. */
    #[Test]
    public function it_captures_the_settings_that_shape_the_event(): void
    {
        $event = $this->bingo();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", ['title' => 'Clan Bingo Night']);

        $settings = EventBlueprint::where('title', 'Clan Bingo Night')->firstOrFail()->settings;

        $this->assertSame('TEAM', $settings['mode']);
        $this->assertSame('GUILD', $settings['access_mode']);
        $this->assertFalse($settings['is_listed']);
        $this->assertSame(4, $settings['bingo_size']);
        $this->assertSame('FULL_HOUSE', $settings['win_condition']);
        $this->assertSame(['ROW', 'COLUMN'], $settings['win_lines']);
        $this->assertSame(3, $settings['line_bonus']);
        $this->assertTrue($settings['requires_approval']);
    }

    #[Test]
    public function a_board_event_captures_its_grid_and_roll_limit(): void
    {
        $event = $this->board();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", ['title' => 'Weekend format']);

        $settings = EventBlueprint::where('title', 'Weekend format')->firstOrFail()->settings;

        $this->assertSame('SIZE_7X7', $settings['size']);
        $this->assertSame(2, $settings['dice_roll_limit']);
        // Nothing from the other type leaks in.
        $this->assertArrayNotHasKey('bingo_size', $settings);
    }

    /**
     * The dates are the one thing a reusable format must not carry. A
     * template that starts every event in July is a template nobody can use
     * in August.
     */
    #[Test]
    public function it_does_not_carry_last_months_dates(): void
    {
        $event = $this->bingo();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", ['title' => 'Clan Bingo Night']);

        $settings = EventBlueprint::where('title', 'Clan Bingo Night')->firstOrFail()->settings;

        $this->assertArrayNotHasKey('start_date', $settings);
        $this->assertArrayNotHasKey('end_date', $settings);
    }

    /** A copy. Changing the event afterwards must not reach the template. */
    #[Test]
    public function editing_the_event_afterwards_leaves_the_template_alone(): void
    {
        $event = $this->bingo();
        $host = $this->host($event);

        $this->actingAs($host)->postJson("/events/{$event->id}/blueprint", ['title' => 'Clan Bingo Night']);

        $event->bingoCard->update(['size' => 5, 'win_condition' => 'LINE']);
        $event->update(['mode' => 'SOLO']);

        $settings = EventBlueprint::where('title', 'Clan Bingo Night')->firstOrFail()->settings;

        $this->assertSame(4, $settings['bingo_size']);
        $this->assertSame('FULL_HOUSE', $settings['win_condition']);
        $this->assertSame('TEAM', $settings['mode']);
    }

    #[Test]
    public function somebody_who_does_not_run_the_event_cannot_save_it(): void
    {
        $event = $this->bingo();
        $stranger = $this->host(name: 'Stranger');

        $this->actingAs($stranger)
            ->postJson("/events/{$event->id}/blueprint", ['title' => 'Stolen format'])
            ->assertForbidden();

        $this->assertNull(EventBlueprint::where('title', 'Stolen format')->first());
    }

    #[Test]
    public function a_title_is_required(): void
    {
        $event = $this->bingo();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", [])
            ->assertJsonValidationErrors('title');
    }

    /**
     * The server a template is filed under decides who else sees it, so it
     * is a claim to check rather than a label to accept — the same rule a
     * team's Discord server goes through.
     */
    #[Test]
    public function a_server_you_are_not_in_cannot_be_claimed(): void
    {
        $event = $this->bingo();

        $this->actingAs($this->host($event))
            ->postJson("/events/{$event->id}/blueprint", [
                'title' => 'Impostor',
                'guild_id' => '999999999999999999',
            ])
            ->assertJsonValidationErrors('guild_id');
    }

    // ------------------------------------------------------------ reading

    #[Test]
    public function the_list_carries_the_settings_each_format_would_fill_in(): void
    {
        $event = $this->bingo();
        $host = $this->host($event);

        $this->actingAs($host)->postJson("/events/{$event->id}/blueprint", ['title' => 'Clan Bingo Night']);

        $blueprints = $this->actingAs($host)->getJson('/event-blueprints')->json('blueprints');

        $mine = collect($blueprints)->firstWhere('title', 'Clan Bingo Night');

        $this->assertSame('BINGO', $mine['type']);
        $this->assertSame(4, $mine['settings']['bingo_size']);
        $this->assertSame('clan', $mine['source']);
        $this->assertSame('Host', $mine['author']);
    }

    /** The set that ships with the app belongs to nobody and is marked so. */
    #[Test]
    public function the_seeded_formats_are_marked_as_global(): void
    {
        EventBlueprint::create(['title' => 'Skill of the Week', 'type' => 'SKILL_RACE', 'is_active' => true]);

        $blueprints = $this->actingAs($this->host())->getJson('/event-blueprints')->json('blueprints');

        $this->assertSame('global', collect($blueprints)->firstWhere('title', 'Skill of the Week')['source']);
    }

    /**
     * A format somebody wrote for their clan is not obviously public — it
     * carries their event's settings, and their clan's name in the title as
     * often as not.
     */
    #[Test]
    public function a_format_saved_by_another_clan_is_not_offered(): void
    {
        $event = $this->bingo();
        $theirs = $this->host($event, name: 'TheirHost');

        $this->actingAs($theirs)->postJson("/events/{$event->id}/blueprint", [
            'title' => 'Their private format',
            'guild_id' => self::MY_GUILD,
        ]);

        $outsider = User::factory()->create(['osrs_username' => 'Outsider']);
        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $outsider->assignRole($role);

        $titles = array_column(
            $this->actingAs($outsider)->getJson('/event-blueprints')->json('blueprints'),
            'title',
        );

        $this->assertNotContains('Their private format', $titles);
    }

    #[Test]
    public function a_clan_mate_does_see_it(): void
    {
        $event = $this->bingo();
        $theirs = $this->host($event, name: 'TheirHost');

        $this->actingAs($theirs)->postJson("/events/{$event->id}/blueprint", [
            'title' => 'Clan format',
            'guild_id' => self::MY_GUILD,
        ]);

        $mate = $this->host(name: 'ClanMate');

        $titles = array_column(
            $this->actingAs($mate)->getJson('/event-blueprints')->json('blueprints'),
            'title',
        );

        $this->assertContains('Clan format', $titles);
    }

    /**
     * The settings go into a form that then posts to the create endpoint, so
     * a stored key nobody vetted would be a stored field nobody vetted.
     */
    #[Test]
    public function a_stored_setting_outside_the_allow_list_is_not_handed_out(): void
    {
        EventBlueprint::create([
            'title' => 'Tampered',
            'type' => 'BINGO',
            'is_active' => true,
            'settings' => ['bingo_size' => 3, 'is_admin' => true, 'start_date' => '2026-07-01'],
        ]);

        $blueprints = $this->actingAs($this->host())->getJson('/event-blueprints')->json('blueprints');

        $settings = collect($blueprints)->firstWhere('title', 'Tampered')['settings'];

        $this->assertSame(3, $settings['bingo_size']);
        $this->assertArrayNotHasKey('is_admin', $settings);
        $this->assertArrayNotHasKey('start_date', $settings);
    }

    #[Test]
    public function a_retired_format_stops_being_offered(): void
    {
        EventBlueprint::create(['title' => 'Old format', 'type' => 'BINGO', 'is_active' => false]);

        $titles = array_column(
            $this->actingAs($this->host())->getJson('/event-blueprints')->json('blueprints'),
            'title',
        );

        $this->assertNotContains('Old format', $titles);
    }
}
