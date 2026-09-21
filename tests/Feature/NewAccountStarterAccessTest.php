<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A new account can make events straight away.
 *
 * The permission matrix proves what an account WITHOUT the right is refused;
 * nothing there says a fresh signup has it, which is how a gate that locked
 * out every newcomer passed the whole suite. These sign up the way a person
 * does and then try to host.
 */
class NewAccountStarterAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.discord', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/discord/callback',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function newEvent(string $title): array
    {
        return [
            'title' => $title,
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ];
    }

    private function discordAnswers(string $discordId): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw(['global_name' => 'Newcomer'])->map([
            'id' => $discordId,
            'nickname' => 'newcomer',
            'name' => 'newcomer',
            'avatar' => null,
            'token' => 'test-token',
        ]);
        $socialiteUser->user = ['global_name' => 'Newcomer'];

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    #[Test]
    public function an_email_signup_can_make_an_event_immediately(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $this->post('/register', [
            'nickname' => 'Fresh',
            'osrs_username' => 'Fresh Face',
            'email' => 'fresh@example.com',
            'password' => 'TestPass123',
            'password_confirmation' => 'TestPass123',
        ])->assertRedirect();

        $user = User::where('email', 'fresh@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('PLAYER'));
        $this->assertTrue($user->hasPermission('canCreateBoards'));

        $this->actingAs($user)->post('/events', $this->newEvent('First event'))->assertRedirect();

        $this->assertSame(1, Event::where('title', 'First event')->count());
    }

    #[Test]
    public function a_discord_signup_can_make_an_event_immediately(): void
    {
        $this->discordAnswers('555000111');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $user = User::where('discord_id', '555000111')->firstOrFail();

        $this->assertTrue($user->hasRole('PLAYER'));
        $this->assertTrue($user->hasPermission('canCreateBoards'));
        $this->assertSame('Standaard spelerrol', $user->roles->firstWhere('name', 'PLAYER')->description);
    }

    /** Signing in again must not hand back what an admin took away. */
    #[Test]
    public function a_returning_discord_login_does_not_regrant_a_revoked_permission(): void
    {
        $this->discordAnswers('555000222');
        $this->get('/auth/discord/callback?code=whatever');

        $user = User::where('discord_id', '555000222')->firstOrFail();
        $user->revokePermissionTo('canCreateBoards');
        auth()->logout();

        $this->discordAnswers('555000222');
        $this->get('/auth/discord/callback?code=whatever');

        $this->assertFalse($user->fresh()->hasPermission('canCreateBoards'));
    }

    /** The grant is per account: it must not turn into a rule for existing PLAYER accounts. */
    #[Test]
    public function an_account_that_already_existed_is_not_touched(): void
    {
        $existing = User::factory()->create(['osrs_username' => 'Old Hand']);
        $existing->assignRole(Role::findOrCreate('PLAYER', 'web'));

        Http::fake(['*' => Http::response([], 404)]);
        $this->post('/register', [
            'nickname' => 'Fresh',
            'osrs_username' => 'Fresh Face',
            'email' => 'fresh@example.com',
            'password' => 'TestPass123',
            'password_confirmation' => 'TestPass123',
        ]);
        auth()->logout();

        $this->assertFalse($existing->fresh()->hasPermission('canCreateBoards'));
        $this->actingAs($existing)->post('/events', $this->newEvent('Nope'))->assertForbidden();
    }
}
