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
 * What a team looks like when it has no icon of its own.
 *
 * Three steps, decided in this order: the team's own `icon_url`, then the
 * linked Discord server's icon, then the initials `UAvatar` builds from the
 * name. Only the middle one needs a server: it is stored on the team at save
 * time rather than read from `user_guilds` when the page renders, because
 * that table only holds the servers of accounts that have logged in — read
 * from there, the same public event would show the clan icon to a clan mate
 * and an empty box to a stranger.
 */
class TeamAvatarFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private function user(?string $icon = 'abc123'): User
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake', 'discord_id' => '42']);

        UserGuild::create([
            'user_id' => $user->id,
            'guild_id' => self::GUILD,
            'guild_name' => 'My Clan',
            'guild_icon' => $icon,
        ]);

        return $user;
    }

    #[Test]
    public function linking_a_server_stores_its_icon_alongside_its_name(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', [
            'name' => 'Blue',
            'guild_id' => self::GUILD,
        ])->assertRedirect();

        $team = Team::where('name', 'Blue')->firstOrFail();

        $this->assertSame('abc123', $team->guild_icon);
        $this->assertSame(
            'https://cdn.discordapp.com/icons/'.self::GUILD.'/abc123.png?size=64',
            $team->guild_icon_url,
        );
    }

    /**
     * An animated guild icon's hash is prefixed `a_`. Asking Discord for it
     * as .png works but serves the still frame, so the extension has to
     * follow the hash.
     */
    #[Test]
    public function an_animated_server_icon_keeps_its_extension(): void
    {
        $user = $this->user('a_deadbeef');

        $this->actingAs($user)->post('/teams', ['name' => 'Blue', 'guild_id' => self::GUILD])->assertRedirect();

        $this->assertStringEndsWith('a_deadbeef.gif?size=64', Team::where('name', 'Blue')->firstOrFail()->guild_icon_url);
    }

    /** A server without an icon is a normal state, not a broken one. */
    #[Test]
    public function a_server_without_an_icon_falls_through_to_nothing(): void
    {
        $user = $this->user(null);

        $this->actingAs($user)->post('/teams', ['name' => 'Blue', 'guild_id' => self::GUILD])->assertRedirect();

        $this->assertNull(Team::where('name', 'Blue')->firstOrFail()->guild_icon_url);
    }

    #[Test]
    public function a_team_on_no_server_has_no_borrowed_icon(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/teams', ['name' => 'Loose'])->assertRedirect();

        $this->assertNull(Team::where('name', 'Loose')->firstOrFail()->guild_icon_url);
    }

    /**
     * The icon leaves with the server for the same reason the name does — a
     * team that is no longer on a clan's server must not keep wearing its
     * badge.
     */
    #[Test]
    public function taking_a_team_off_its_server_drops_the_borrowed_icon(): void
    {
        $user = $this->user();

        $team = Team::create([
            'name' => 'Blue',
            'guild_id' => self::GUILD,
            'guild_name' => 'My Clan',
            'guild_icon' => 'abc123',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)->patch("/teams/{$team->id}", [
            'name' => 'Blue',
            'guild_id' => null,
        ])->assertRedirect();

        $this->assertNull($team->fresh()->guild_icon);
    }

    /** A rename is a partial update and must not strip the borrowed icon. */
    #[Test]
    public function renaming_a_team_keeps_the_borrowed_icon(): void
    {
        $user = $this->user();

        $team = Team::create([
            'name' => 'Blue',
            'guild_id' => self::GUILD,
            'guild_name' => 'My Clan',
            'guild_icon' => 'abc123',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)->patch("/teams/{$team->id}", ['name' => 'Green'])->assertRedirect();

        $this->assertSame('abc123', $team->fresh()->guild_icon);
    }

    /**
     * The page can only fall back to something it was sent. This is the half
     * that actually broke before: the model could resolve the URL all day and
     * the teams list still rendered an empty box without it in the payload.
     */
    #[Test]
    public function the_teams_page_ships_the_fallback_it_needs(): void
    {
        $user = $this->user();

        $team = Team::create([
            'name' => 'Blue',
            'guild_id' => self::GUILD,
            'guild_name' => 'My Clan',
            'guild_icon' => 'abc123',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)
            ->get('/teams')
            ->assertInertia(fn ($page) => $page
                ->where('teams.0.name', 'Blue')
                ->where('teams.0.guild_icon_url', 'https://cdn.discordapp.com/icons/'.self::GUILD.'/abc123.png?size=64'));
    }
}
