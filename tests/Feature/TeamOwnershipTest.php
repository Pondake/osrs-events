<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Team management used to ask one global question — admin, or the
 * TEAM_MANAGER role? — which produced the reported complaint exactly: you
 * could create a team and then do nothing whatsoever with it, no rename, no
 * members, no delete, while a single role granted all of that over every
 * team on the site.
 *
 * The rules these pin down:
 *   - creating a team makes you its OWNER, and owners manage
 *   - an owner can promote a member to MANAGER, who manages but cannot delete
 *   - a plain MEMBER manages nothing
 *   - the owner cannot be demoted or removed, or the team is left with
 *     nobody who can delete it and no way to appoint one
 */
class TeamOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $name): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    /** A team plus its creator-as-owner, the shape store() produces. */
    private function teamOwnedBy(User $owner, string $name = 'Zulrah Enjoyers'): Team
    {
        $team = Team::create(['name' => $name]);

        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::OWNER]);

        return $team;
    }

    /**
     * The role this replaced grants nothing any more.
     *
     * `TEAM_MANAGER` let anyone holding it rename, staff and restaff **every
     * team on the site**. The per-team roles above took that job over on
     * 2026-08-21, and it was kept alive for one deploy so nobody lost access
     * mid-flight — a temporary measure that outlived its reason by three days.
     *
     * Pinned as a test rather than trusted to the migration, because the
     * migration only deletes the row: nothing stops the role being created
     * again by hand from the admin users page, and if the check ever came
     * back that would silently restore site-wide team management to whoever
     * happened to have the name.
     */
    #[Test]
    public function the_retired_global_role_no_longer_manages_other_peoples_teams(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $outsider = $this->player('Bystander');
        $outsider->assignRole(Role::findOrCreate('TEAM_MANAGER', 'web'));

        $this->assertFalse($team->isManagedBy($outsider));

        $this->actingAs($outsider)
            ->patch("/teams/{$team->id}", ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Zulrah Enjoyers', $team->fresh()->name);
    }

    /** Admin still is the answer to "somebody has to be able to fix this". */
    #[Test]
    public function an_admin_still_manages_a_team_they_are_not_in(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $admin = $this->player('Staff');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->assertTrue($team->isManagedBy($admin));
    }

    #[Test]
    public function creating_a_team_makes_you_its_owner(): void
    {
        $user = $this->player('Founder');

        $this->actingAs($user)->post('/teams', ['name' => 'Fresh Team'])->assertRedirect();

        $team = Team::where('name', 'Fresh Team')->firstOrFail();

        $this->assertSame(TeamMember::OWNER, $team->members()->where('user_id', $user->id)->value('role'));
    }

    /** The reported bug, in one assertion. */
    #[Test]
    public function the_creator_can_rename_their_own_team_and_add_members(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);
        $recruit = $this->player('Recruit');

        $this->actingAs($owner)->patch("/teams/{$team->id}", ['name' => 'Renamed'])->assertRedirect();
        $this->assertSame('Renamed', $team->fresh()->name);

        $this->actingAs($owner)->post("/teams/{$team->id}/members", ['user_id' => $recruit->id])->assertRedirect();
        $this->assertSame(
            TeamMember::MEMBER,
            $team->members()->where('user_id', $recruit->id)->value('role'),
        );
    }

    #[Test]
    public function a_plain_member_manages_nothing(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $member = $this->player('Regular');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $this->actingAs($member)->patch("/teams/{$team->id}", ['name' => 'Hijacked'])->assertForbidden();
        $this->actingAs($member)->post("/teams/{$team->id}/members", ['user_id' => $this->player('X')->id])->assertForbidden();
        $this->actingAs($member)->delete("/teams/{$team->id}")->assertForbidden();

        $this->assertSame('Zulrah Enjoyers', $team->fresh()->name);
    }

    #[Test]
    public function an_owner_can_promote_a_member_who_then_manages_but_cannot_delete(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $member = $this->player('Deputy');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $this->actingAs($owner)
            ->patch("/teams/{$team->id}/members/{$member->id}", ['role' => TeamMember::MANAGER])
            ->assertRedirect();

        $this->actingAs($member)->patch("/teams/{$team->id}", ['name' => 'Managed'])->assertRedirect();
        $this->assertSame('Managed', $team->fresh()->name);

        // The one thing a manager still cannot do — it takes the team's
        // whole history with it.
        $this->actingAs($member)->delete("/teams/{$team->id}")->assertForbidden();
        $this->assertNotNull($team->fresh());
    }

    /** Promoting is the owner's alone, so managers cannot multiply. */
    #[Test]
    public function a_manager_cannot_promote_anyone_else(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $manager = $this->player('Deputy');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $manager->id, 'role' => TeamMember::MANAGER]);

        $other = $this->player('Someone');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $other->id, 'role' => TeamMember::MEMBER]);

        $this->actingAs($manager)
            ->patch("/teams/{$team->id}/members/{$other->id}", ['role' => TeamMember::MANAGER])
            ->assertForbidden();
    }

    #[Test]
    public function the_owner_cannot_be_demoted_or_removed(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $this->actingAs($owner)
            ->patch("/teams/{$team->id}/members/{$owner->id}", ['role' => TeamMember::MEMBER])
            ->assertForbidden();

        $this->actingAs($owner)->delete("/teams/{$team->id}/members/{$owner->id}")->assertForbidden();

        $this->assertSame(TeamMember::OWNER, $team->members()->where('user_id', $owner->id)->value('role'));
    }

    #[Test]
    public function the_owner_can_delete_the_team(): void
    {
        $owner = $this->player('Founder');
        $team = $this->teamOwnedBy($owner);

        $this->actingAs($owner)->delete("/teams/{$team->id}")->assertRedirect();

        $this->assertNull(Team::find($team->id));
    }

    /**
     * The page renders its buttons off these, so getting them wrong shows up
     * as a card someone cannot use rather than as an error.
     */
    #[Test]
    public function the_index_ships_a_per_team_permission_flag_rather_than_one_global_one(): void
    {
        $owner = $this->player('Founder');
        $this->teamOwnedBy($owner);

        $ownerProps = $this->actingAs($owner)->get('/teams')->viewData('page')['props']['teams'];

        $this->assertTrue($ownerProps[0]['canManage']);
        $this->assertTrue($ownerProps[0]['canDelete']);
        $this->assertSame(TeamMember::OWNER, $ownerProps[0]['viewerRole']);
    }

    /**
     * A team with no Discord server is a private group, not an unclaimed
     * one. The old rule ("your guilds, plus any team with no guild") made
     * every one of them visible to every account on the site, which is what
     * put strangers' teams in the event form's picker.
     */
    #[Test]
    public function a_team_you_are_not_in_and_share_no_server_with_is_invisible(): void
    {
        $this->teamOwnedBy($this->player('Founder'));

        $outsider = $this->player('Outsider');

        $this->assertCount(0, $this->actingAs($outsider)->get('/teams')->viewData('page')['props']['teams']);
        $this->actingAs($outsider)->getJson('/teams/options')->assertOk()->assertJsonPath('teams', []);
    }

    #[Test]
    public function a_team_on_a_discord_server_you_are_in_is_visible(): void
    {
        $team = $this->teamOwnedBy($this->player('Founder'));
        $team->update(['guild_id' => '123456789012345678', 'guild_name' => 'Zulrah Enjoyers']);

        $clanmate = $this->player('Clanmate');
        UserGuild::create([
            'user_id' => $clanmate->id,
            'guild_id' => '123456789012345678',
            'guild_name' => 'Zulrah Enjoyers',
        ]);

        $teams = $this->actingAs($clanmate)->get('/teams')->viewData('page')['props']['teams'];

        $this->assertCount(1, $teams);
        // Visible, but not theirs to manage — seeing is not membership.
        $this->assertFalse($teams[0]['canManage']);
        $this->assertNull($teams[0]['viewerRole']);
    }

    /** Being a plain member is enough to see it. */
    #[Test]
    public function a_team_you_are_a_member_of_is_visible(): void
    {
        $team = $this->teamOwnedBy($this->player('Founder'));

        $member = $this->player('Regular');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $this->assertCount(1, $this->actingAs($member)->get('/teams')->viewData('page')['props']['teams']);
    }

    /**
     * The list says WHY each team is on it.
     *
     * Visibility was scoped, but the page said nothing about which rule let
     * each team through — so an admin saw everything with no way to tell
     * their own teams from a clan mate's from one they can only see because
     * they are an admin. Decided server-side, because the client has no
     * business knowing which Discord servers somebody is in.
     */
    #[Test]
    public function each_team_says_why_it_is_visible(): void
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));
        UserGuild::create(['user_id' => $admin->id, 'guild_id' => '111', 'guild_name' => 'My Clan']);

        $mine = Team::create(['name' => 'Mine']);
        TeamMember::create(['team_id' => $mine->id, 'user_id' => $admin->id, 'role' => TeamMember::OWNER]);

        Team::create(['name' => 'From my server', 'guild_id' => '111', 'guild_name' => 'My Clan']);
        Team::create(['name' => 'Somebody elses', 'guild_id' => '999', 'guild_name' => 'Other Clan']);

        $teams = collect($this->actingAs($admin)->get('/teams')->viewData('page')['props']['teams'])
            ->keyBy('name');

        $this->assertSame('member', $teams['Mine']['reason']);
        $this->assertSame('guild', $teams['From my server']['reason']);
        $this->assertSame('admin', $teams['Somebody elses']['reason']);
    }

    /**
     * A team can qualify under more than one rule at once — your own team on
     * your own server — and the strongest claim is the one worth showing.
     */
    #[Test]
    public function membership_outranks_the_server_it_belongs_to(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);
        UserGuild::create(['user_id' => $user->id, 'guild_id' => '111', 'guild_name' => 'My Clan']);

        $team = Team::create(['name' => 'Both', 'guild_id' => '111', 'guild_name' => 'My Clan']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::MEMBER]);

        $teams = $this->actingAs($user)->get('/teams')->viewData('page')['props']['teams'];

        $this->assertSame('member', collect($teams)->firstWhere('name', 'Both')['reason']);
    }

    #[Test]
    public function an_admin_still_manages_every_team(): void
    {
        $team = $this->teamOwnedBy($this->player('Founder'));

        $admin = $this->player('TheAdmin');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->patch("/teams/{$team->id}", ['name' => 'Admin Renamed'])->assertRedirect();
        $this->assertSame('Admin Renamed', $team->fresh()->name);
    }

    /**
     * The member search backs an add-member picker, and was reachable by
     * anyone who could guess a team id — it listed every account on the site
     * with no permission check at all.
     */
    #[Test]
    public function the_member_search_is_closed_to_people_who_cannot_manage_the_team(): void
    {
        $team = $this->teamOwnedBy($this->player('Founder'));

        $this->actingAs($this->player('Nosy'))
            ->getJson("/teams/{$team->id}/users/search?search=a")
            ->assertForbidden();
    }
}
