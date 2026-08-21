<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Which Discord server a team says it belongs to.
 *
 * This field is load-bearing twice over. It is what the teams list shows
 * beside a team's name, so people read it as a fact about who the team is.
 * And `Team::scopeVisibleTo` uses it to decide who can see the team at all —
 * a team carrying a guild id is visible to every member of that guild.
 *
 * So it is a claim the server has to check, not a label the client picks.
 */
class TeamGuildClaimTest extends TestCase
{
    use RefreshDatabase;

    private const MINE = '111111111111111111';

    private const NOT_MINE = '999999999999999999';

    private function user(): User
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake', 'discord_id' => '42']);

        UserGuild::create([
            'user_id' => $user->id,
            'guild_id' => self::MINE,
            'guild_name' => 'My Clan',
        ]);

        return $user;
    }

    #[Test]
    public function a_team_can_be_put_on_a_server_you_are_in(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', [
            'name' => 'Blue',
            'guild_id' => self::MINE,
            'guild_name' => 'My Clan',
        ])->assertRedirect();

        $this->assertSame(self::MINE, Team::where('name', 'Blue')->firstOrFail()->guild_id);
    }

    #[Test]
    public function a_team_can_belong_to_no_server_at_all(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', ['name' => 'Loose'])->assertRedirect();

        $this->assertNull(Team::where('name', 'Loose')->firstOrFail()->guild_id);
    }

    /**
     * Claiming a server you are not in does two things, both bad: it labels
     * your team with somebody else's clan name, and it publishes your team to
     * every member of that clan through scopeVisibleTo.
     */
    #[Test]
    public function a_server_you_are_not_in_cannot_be_claimed_on_create(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', [
            'name' => 'Impostor',
            'guild_id' => self::NOT_MINE,
            'guild_name' => 'Somebody Elses Clan',
        ])->assertSessionHasErrors('guild_id');

        $this->assertNull(Team::where('name', 'Impostor')->first());
    }

    #[Test]
    public function a_server_you_are_not_in_cannot_be_claimed_on_edit(): void
    {
        $user = $this->user();

        $team = Team::create(['name' => 'Blue', 'guild_id' => self::MINE, 'guild_name' => 'My Clan']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)->patch("/teams/{$team->id}", [
            'name' => 'Blue',
            'guild_id' => self::NOT_MINE,
            'guild_name' => 'Somebody Elses Clan',
        ])->assertSessionHasErrors('guild_id');

        $this->assertSame(self::MINE, $team->fresh()->guild_id);
    }

    /**
     * The name shown beside the team has to be the server's real name, not
     * whatever came up with the id — otherwise the check above is bypassed by
     * claiming a server you ARE in and labelling it as something else.
     */
    #[Test]
    public function the_server_name_comes_from_the_server_not_the_form(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', [
            'name' => 'Blue',
            'guild_id' => self::MINE,
            'guild_name' => 'Totally Different Clan',
        ])->assertRedirect();

        $this->assertSame('My Clan', Team::where('name', 'Blue')->firstOrFail()->guild_name);
    }

    /**
     * Renaming a team must not quietly drop the rest of it — a form that
     * sends one field is a partial update, not a replacement.
     */
    #[Test]
    public function renaming_a_team_keeps_its_server_and_icon(): void
    {
        $user = $this->user();

        $team = Team::create([
            'name' => 'Blue',
            'guild_id' => self::MINE,
            'guild_name' => 'My Clan',
            'icon_url' => 'https://example.com/icon.png',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)->patch("/teams/{$team->id}", ['name' => 'Green'])->assertRedirect();

        $fresh = $team->fresh();

        $this->assertSame('Green', $fresh->name);
        $this->assertSame(self::MINE, $fresh->guild_id);
        $this->assertSame('https://example.com/icon.png', $fresh->icon_url);
    }

    /**
     * The picker binds to a string, so "no server" arrives as an empty one
     * rather than as a missing key. Laravel's ConvertEmptyStringsToNull is
     * what makes that land as null and skip the exists rule — worth pinning,
     * because without it every team created without a server would fail
     * validation against a guild id of "".
     */
    #[Test]
    public function an_empty_server_field_means_no_server_rather_than_a_bad_one(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', [
            'name' => 'Loose',
            'guild_id' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Team::where('name', 'Loose')->firstOrFail()->guild_id);
    }

    /** Taking a team off a server is a thing an owner may legitimately do. */
    #[Test]
    public function a_team_can_be_taken_off_its_server(): void
    {
        $user = $this->user();

        $team = Team::create(['name' => 'Blue', 'guild_id' => self::MINE, 'guild_name' => 'My Clan']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)->patch("/teams/{$team->id}", [
            'name' => 'Blue',
            'guild_id' => null,
        ])->assertRedirect();

        $fresh = $team->fresh();

        $this->assertNull($fresh->guild_id);
        // The label goes with it — a name with no id behind it is the same
        // unverified claim by another route.
        $this->assertNull($fresh->guild_name);
    }
}
