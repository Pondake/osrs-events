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
 * Names follow the same rule as the event page and its leaderboard: public on
 * an OPEN event, otherwise only for the people in it and the people running it.
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

        $entrant = $this->player('Main Sample');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Main Sample',
            'start_value' => 0,
        ]);

        $props = $this->props($host, $event);

        $this->assertTrue($props['named']);
        $this->assertContains('Main Sample', collect($props['participants'])->pluck('osrsUsername')->all());
    }

    /** Being in it is enough — you can see who you are up against. */
    #[Test]
    public function somebody_taking_part_sees_the_names(): void
    {
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $entrant = $this->player('Main Sample');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Main Sample',
            'start_value' => 0,
        ]);

        $this->assertTrue($this->props($entrant, $event)['named']);
    }

    /** Same rule as the event page and its leaderboard. */
    #[Test]
    public function a_stranger_on_an_open_event_sees_the_names(): void
    {
        $event = $this->event();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $props = $this->props($this->player('Nosy'), $event);

        $this->assertTrue($props['named']);
        $this->assertCount(1, $props['participants']);
    }

    #[Test]
    public function a_stranger_on_a_listed_invite_only_event_sees_a_count_and_no_names(): void
    {
        $event = $this->event(['access_mode' => 'INVITE']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $entrant = $this->player('Main Sample');
        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $entrant->id,
            'username' => 'Main Sample',
            'start_value' => 0,
        ]);

        $props = $this->props($this->player('Nosy'), $event);

        $this->assertFalse($props['named']);
        $this->assertSame([], $props['participants']);
        // The count still renders — an empty page would read as "nobody is
        // playing", which is a different and wrong statement.
        $this->assertSame(2, $props['participantCount']);
    }

    /** Readable signed out, and a guest is the strangest stranger there is. */
    #[Test]
    public function a_signed_out_reader_gets_names_only_on_an_open_event(): void
    {
        $open = $this->event();
        BoardAuthor::create(['event_id' => $open->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $this->get("/events/{$open->id}/participants")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('named', true)
                ->where('canEdit', false)
                ->has('participants', 1));

        $invite = $this->event(['access_mode' => 'INVITE']);
        BoardAuthor::create(['event_id' => $invite->id, 'user_id' => $this->player('OtherHost')->id, 'is_owner' => true]);

        $this->get("/events/{$invite->id}/participants")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('named', false)
                ->where('participants', [])
                ->where('participantCount', 1));
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
        $event = $this->event(['access_mode' => 'INVITE']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Host')->id, 'is_owner' => true]);

        $admin = $this->player('TheAdmin');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->assertTrue($this->props($admin, $event)['named']);
    }

    #[Test]
    public function a_stranger_cannot_open_the_participants_of_a_private_event(): void
    {
        $event = $this->event(['is_listed' => false, 'access_mode' => 'INVITE']);

        $this->actingAs($this->player('Stranger'))
            ->get("/events/{$event->id}/participants")
            ->assertForbidden();
    }
}
