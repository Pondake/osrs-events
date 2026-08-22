<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a password change does to the sessions that are already open.
 *
 * It signs them out. That is the point of changing a password: somebody does
 * it when they think a session is not theirs, and a change that left those
 * sessions running would answer the wrong question.
 *
 * Two pieces make it work. `AuthenticateSession` keeps a copy of the password
 * hash in each session and turns away any session whose copy has gone stale;
 * `logoutOtherDevices()` forces a re-hash so that every such copy — in a
 * session or in a "keep me signed in" cookie — stops matching at once. The
 * middleware also has to leave the session doing the changing alone, or the
 * person would be thrown out by their own action.
 */
class SessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'Correct-horse-1';

    private const NEW = 'Battery-staple-2';

    private function user(): User
    {
        return User::factory()->create([
            'osrs_username' => 'Pondake',
            'email' => 'me@example.com',
            'password' => Hash::make(self::OLD),
        ]);
    }

    private function changePassword(User $user): void
    {
        $this->actingAs($user)->put('/settings/account/password', [
            'current_password' => self::OLD,
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertSessionHasNoErrors();
    }

    /**
     * The other session is simulated the way the middleware sees one: signed
     * in, carrying the hash that was current when it started.
     */
    #[Test]
    public function a_session_opened_before_the_change_is_signed_out(): void
    {
        $user = $this->user();
        $staleHash = $user->getAuthPassword();

        $this->changePassword($user);

        $this->flushSession();

        $this->withSession(['password_hash_web' => $staleHash])
            ->actingAs($user->fresh())
            ->get('/settings/account')
            ->assertRedirect('/login');
    }

    /** The person doing it is not thrown out by their own action. */
    #[Test]
    public function the_session_making_the_change_survives_it(): void
    {
        $user = $this->user();

        $this->changePassword($user);

        $this->get('/settings/account')->assertOk();
    }

    /** A session already carrying the new hash is fine. */
    #[Test]
    public function a_session_opened_after_the_change_is_left_alone(): void
    {
        $user = $this->user();
        $this->changePassword($user);

        $this->flushSession();

        $this->actingAs($user->fresh())
            ->get('/settings/account')
            ->assertOk();
    }

    /**
     * The stored hash is the thing everything else is compared against.
     *
     * Not the remember token — `logoutOtherDevices()` does not touch it. What
     * it does is force a re-hash of the password, and both a stale session
     * and a "keep me signed in" cookie carry their own copy of the old hash
     * to be checked against this one. Change it and they all stop matching at
     * once, which is why one assertion covers both routes back in.
     */
    #[Test]
    public function the_stored_hash_no_longer_matches_what_older_sessions_carry(): void
    {
        $user = $this->user();
        $carriedBySessionsAndCookies = $user->getAuthPassword();

        $this->changePassword($user);

        $this->assertNotSame($carriedBySessionsAndCookies, $user->fresh()->getAuthPassword());
        // And the old password is genuinely gone, not merely re-hashed.
        $this->assertFalse(Hash::check(self::OLD, $user->fresh()->getAuthPassword()));
        $this->assertTrue(Hash::check(self::NEW, $user->fresh()->getAuthPassword()));
    }

    /**
     * A Discord login has no password at all, and the middleware returns
     * early for exactly that case. Worth pinning: it is most of the user base
     * here, and a middleware that mishandled it would sign everybody out on
     * every request.
     */
    #[Test]
    public function an_account_with_no_password_is_untouched_by_any_of_this(): void
    {
        $user = User::factory()->create([
            'osrs_username' => 'Pondake',
            'discord_id' => '123',
            'email' => null,
            'password' => null,
        ]);

        $this->actingAs($user)->get('/settings/account')->assertOk();
        $this->actingAs($user)->get('/settings/account')->assertOk();
    }

    /** And an ordinary signed-in session is not disturbed by the middleware. */
    #[Test]
    public function a_passworded_account_can_browse_across_requests(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/settings/account')->assertOk();
        $this->get('/settings/profile')->assertOk();
        $this->get('/settings/account')->assertOk();
    }
}
