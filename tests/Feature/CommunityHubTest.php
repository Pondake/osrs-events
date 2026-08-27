<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `/community` — same shape as the events hub: a slice of each thing the
 * Community nav group advertises (Teams/Leaderboards/Clans), which previously
 * had no shared landing spot at all.
 */
class CommunityHubTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_a_slice_of_the_users_own_teams(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $team = Team::create(['name' => 'Iron Fist']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => 'OWNER']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => User::factory()->create()->id, 'role' => 'MEMBER']);

        $props = $this->actingAs($user)->get('/community')->assertOk()->viewData('page')['props'];

        $this->assertCount(1, $props['teams']);
        $this->assertSame('Iron Fist', $props['teams'][0]['name']);
        $this->assertSame(2, $props['teams'][0]['memberCount']);
        $this->assertSame('OWNER', $props['teams'][0]['viewerRole']);
        $this->assertSame(1, $props['teamsTotal']);
    }

    /** Someone else's team, with no shared server, is nobody's business but theirs. */
    #[Test]
    public function a_team_the_user_is_not_in_is_not_shown(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);
        $other = User::factory()->create(['osrs_username' => 'Someone']);

        $team = Team::create(['name' => 'Not Yours']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $other->id, 'role' => 'OWNER']);

        $props = $this->actingAs($user)->get('/community')->assertOk()->viewData('page')['props'];

        $this->assertCount(0, $props['teams']);
        $this->assertSame(0, $props['teamsTotal']);
    }

    #[Test]
    public function it_requires_being_signed_in(): void
    {
        $this->get('/community')->assertRedirect('/login');
    }
}
