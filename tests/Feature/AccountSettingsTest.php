<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Changing the email address and the password.
 *
 * Two accounts shapes meet here and the rules differ between them. A Discord
 * login has no password and, because the OAuth scopes deliberately leave out
 * email, no email either — so it has to be able to set both from nothing. An
 * account that already has a password is a different matter: from that point
 * on, the email address is the thing a reset link goes to, which makes
 * changing it equivalent to changing the password.
 */
class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function withPassword(string $password = 'Correct-horse-1'): User
    {
        return User::factory()->create([
            'osrs_username' => 'Pondake',
            'email' => 'old@example.com',
            'password' => Hash::make($password),
        ]);
    }

    /** A Discord login: no password, and no email either. */
    private function discordOnly(): User
    {
        return User::factory()->create([
            'osrs_username' => 'Pondake',
            'discord_id' => '123',
            'email' => null,
            'password' => null,
        ]);
    }

    // ------------------------------------------------------------ the email

    /**
     * The whole reason this endpoint exists — a Discord login has nothing to
     * send a reset link to until it does this.
     */
    #[Test]
    public function a_discord_login_can_set_an_email_for_the_first_time(): void
    {
        $user = $this->discordOnly();

        $this->actingAs($user)
            ->put('/settings/account/email', ['email' => 'me@example.com'])
            ->assertRedirect();

        $this->assertSame('me@example.com', $user->fresh()->email);
    }

    /**
     * With a password set, the email address IS the recovery path. Changing
     * it from a session alone turns any borrowed session into a permanent
     * takeover: point the address somewhere else, then ask for a reset link.
     * So it takes the password, exactly as changing the password does.
     */
    #[Test]
    public function changing_the_email_on_a_passworded_account_takes_the_password(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)
            ->put('/settings/account/email', ['email' => 'attacker@example.com'])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    #[Test]
    public function the_right_password_lets_the_email_through(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/email', [
            'email' => 'new@example.com',
            'current_password' => 'Correct-horse-1',
        ])->assertRedirect();

        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    #[Test]
    public function the_wrong_password_does_not(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/email', [
            'email' => 'new@example.com',
            'current_password' => 'not-it',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    #[Test]
    public function an_email_already_in_use_is_refused(): void
    {
        User::factory()->create(['osrs_username' => 'Other', 'email' => 'taken@example.com']);
        $user = $this->discordOnly();

        $this->actingAs($user)
            ->put('/settings/account/email', ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    }

    /** Saving your own address back is not a collision with yourself. */
    #[Test]
    public function keeping_your_own_address_is_not_a_collision(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/email', [
            'email' => 'old@example.com',
            'current_password' => 'Correct-horse-1',
        ])->assertSessionHasNoErrors();
    }

    // --------------------------------------------------------- the password

    #[Test]
    public function a_discord_login_can_set_a_password_once_it_has_an_email(): void
    {
        $user = $this->discordOnly();
        $user->update(['email' => 'me@example.com']);

        $this->actingAs($user)->put('/settings/account/password', [
            'password' => 'Correct-horse-1',
            'password_confirmation' => 'Correct-horse-1',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('Correct-horse-1', $user->fresh()->password));
    }

    /**
     * A password with no address to reset it against is a lockout waiting to
     * happen. The form hides itself in this state; this is the actual guard.
     */
    #[Test]
    public function a_password_cannot_be_set_without_an_email_to_recover_it(): void
    {
        $user = $this->discordOnly();

        $this->actingAs($user)->put('/settings/account/password', [
            'password' => 'Correct-horse-1',
            'password_confirmation' => 'Correct-horse-1',
        ])->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->password);
    }

    #[Test]
    public function changing_a_password_takes_the_old_one(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/password', [
            'password' => 'Battery-staple-2',
            'password_confirmation' => 'Battery-staple-2',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Correct-horse-1', $user->fresh()->password));
    }

    #[Test]
    public function the_old_password_lets_the_new_one_through(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/password', [
            'current_password' => 'Correct-horse-1',
            'password' => 'Battery-staple-2',
            'password_confirmation' => 'Battery-staple-2',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('Battery-staple-2', $user->fresh()->password));
    }

    #[Test]
    public function a_new_password_has_to_be_confirmed_and_strong(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)->put('/settings/account/password', [
            'current_password' => 'Correct-horse-1',
            'password' => 'Battery-staple-2',
            'password_confirmation' => 'Battery-staple-3',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put('/settings/account/password', [
            'current_password' => 'Correct-horse-1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Correct-horse-1', $user->fresh()->password));
    }

    // ------------------------------------------------------------- the page

    #[Test]
    public function the_page_says_which_ways_in_the_account_has(): void
    {
        $user = $this->withPassword();
        $user->update(['discord_id' => '123']);

        $props = $this->actingAs($user)->get('/settings/account')->viewData('page')['props'];

        $this->assertSame('old@example.com', $props['email']);
        $this->assertTrue($props['hasPassword']);
    }

    /**
     * Discord moved to Settings → Connections on 2026-08-30, along with the
     * Wise Old Man key. `hasPassword` comes with it because the disconnect
     * button is disabled without one — an account whose only way in is
     * Discord may not unlink it.
     */
    #[Test]
    public function the_connections_page_says_which_services_are_linked(): void
    {
        $user = $this->withPassword();
        $user->update(['discord_id' => '123']);

        $props = $this->actingAs($user)->get('/settings/connections')->viewData('page')['props'];

        $this->assertTrue($props['hasDiscord']);
        $this->assertTrue($props['hasPassword']);
    }

    /** The hash itself has no business leaving the server. */
    #[Test]
    public function the_page_does_not_carry_the_password_hash(): void
    {
        $user = $this->withPassword();

        $this->actingAs($user)
            ->get('/settings/account')
            ->assertDontSee(substr($user->password, 0, 20), escape: false);
    }

    #[Test]
    public function none_of_it_is_reachable_signed_out(): void
    {
        $this->put('/settings/account/email', ['email' => 'me@example.com'])->assertRedirect('/login');
        $this->put('/settings/account/password', [])->assertRedirect('/login');
    }

}
