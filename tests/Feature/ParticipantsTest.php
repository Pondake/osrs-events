<?php

namespace Tests\Feature;

use App\Models\BoardAccess;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who is taking part, and who is allowed to know.
 *
 * The rule worth pinning down is the privacy one: a listed OPEN event is
 * indexed and reachable by anyone, and nobody joined a board game expecting
 * to end up in a public directory of who plays what. Counts are public;
 * names are for the people in it and the people running it.
 */
class ParticipantsTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $name): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Clan night',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            ...$attributes,
        ]);
    }

    private function props(User $viewer, Event $event): array
    {
        return $this->actingAs($viewer)
            ->get("/events/{$event->id}/participants")
            ->viewData('page')['props'];
    }

    #[Test]
    public function a_host_sees_every_name(): void
    {
        $host = $this->player('Host');
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        $entrant = $this->player('Pondake');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Pondake',
            'start_value' => 0,
        ]);

        $props = $this->props($host, $event);

        $this->assertTrue($props['named']);
        $this->assertContains('Pondake', collect($props['participants'])->pluck('osrsUsername')->all());
    }

    /** Being in it is enough — you can see who you are up against. */
    #[Test]
    public function somebody_taking_part_sees_the_names(): void
    {
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $entrant = $this->player('Pondake');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Pondake',
            'start_value' => 0,
        ]);

        $this->assertTrue($this->props($entrant, $event)['named']);
    }

    /** The rule this page exists to get right. */
    #[Test]
    public function a_stranger_on_a_public_event_sees_a_count_and_no_names(): void
    {
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $entrant = $this->player('Pondake');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Pondake',
            'start_value' => 0,
        ]);

        $props = $this->props($this->player('Nosy'), $event);

        $this->assertFalse($props['named']);
        $this->assertSame([], $props['participants']);
        // The count still renders — an empty page would read as "nobody is
        // playing", which is a different and wrong statement.
        $this->assertSame(2, $props['participantCount']);
    }

    #[Test]
    public function an_invited_participant_counts_as_taking_part(): void
    {
        $event = $this->event(['access_mode' => 'INVITE', 'is_listed' => false]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $guest = $this->player('Guest');
        BoardAccess::create(['event_id' => $event->id, 'user_id' => $guest->id, 'access_mode' => 'INVITE']);

        $this->assertTrue($this->props($guest, $event)['named']);
    }

    /**
     * On a TEAM event it is the team that plays, so a member may have no row
     * of their own and would otherwise be a stranger to their own event.
     */
    #[Test]
    public function a_member_of_an_assigned_team_sees_the_names(): void
    {
        $event = $this->event(['mode' => 'TEAM']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $team = Team::create(['name' => 'Zulrah Enjoyers']);
        $member = $this->player('Teammate');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);
        BoardTeam::create(['event_id' => $event->id, 'team_id' => $team->id]);

        $props = $this->props($member, $event);

        $this->assertTrue($props['named']);
        $this->assertSame('Zulrah Enjoyers', $props['teams'][0]['name']);
        $this->assertSame(1, $props['teams'][0]['memberCount']);
    }

    /** A solo event has no teams to list, and says so by listing none. */
    #[Test]
    public function a_solo_event_lists_no_teams(): void
    {
        $host = $this->player('Host');
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        $this->assertSame([], $this->props($host, $event)['teams']);
    }

    #[Test]
    public function an_admin_sees_the_names_of_an_event_they_are_not_in(): void
    {
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $admin = $this->player('TheAdmin');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->assertTrue($this->props($admin, $event)['named']);
    }
}
