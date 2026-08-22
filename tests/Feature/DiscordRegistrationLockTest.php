<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The other way to get an account.
 *
 * Registration is closed while the site is locked, but Discord is two things
 * at once: a way to sign in, and a way to acquire an account without ever
 * seeing a registration form. The OAuth routes have to stay open — an admin
 * signs in through them — so the account-creating half is refused separately,
 * inside the callback.
 *
 * Worth its own file because it is the kind of hole that looks closed from
 * the route list: `/register` redirects to the lock screen and everything
 * reads as shut, while `auth/discord/callback` quietly makes users.
 */
class DiscordRegistrationLockTest extends TestCase
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

    private function lock(): void
    {
        Setting::setMany(['site_lock_enabled' => true, 'site_lock_password' => 'irrelevant-here']);
    }

    /** Stands in for the round trip Discord would have answered. */
    private function discordAnswers(string $discordId, string $username = 'newcomer'): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw(['global_name' => 'Newcomer'])->map([
            'id' => $discordId,
            'nickname' => $username,
            'name' => $username,
            'avatar' => null,
            'token' => 'test-token',
        ]);

        // The raw payload is read directly for global_name, which map() does
        // not carry across.
        $socialiteUser->user = ['global_name' => 'Newcomer'];

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function a_stranger_cannot_make_an_account_through_discord_while_locked(): void
    {
        $this->lock();
        $this->discordAnswers('999888777');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect('/login');

        $this->assertNull(User::where('discord_id', '999888777')->first());
        $this->assertGuest();
    }

    /**
     * The person building the site signs in through this same route, so an
     * account that already exists has to keep working.
     */
    #[Test]
    public function an_account_that_already_exists_still_signs_in(): void
    {
        $this->lock();

        $existing = User::factory()->create([
            'osrs_username' => 'Pondake',
            'discord_id' => '111222333',
        ]);

        $this->discordAnswers('111222333', 'pondake');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $this->assertSame($existing->id, Auth::id());
    }

    #[Test]
    public function an_open_site_still_makes_accounts_as_normal(): void
    {
        $this->discordAnswers('444555666');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $this->assertNotNull(User::where('discord_id', '444555666')->first());
    }
}
