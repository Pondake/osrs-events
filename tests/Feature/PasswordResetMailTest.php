<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The one email this app sends.
 *
 * Password reset is the only outbound mail there is, which makes it the only
 * thing standing between somebody and a locked-out account — so it is worth
 * covering as a whole rather than as three separate steps that each pass.
 * Verified against a real SMTP server (Mailpit) on 2026-08-22; these tests
 * pin the parts that do not need one.
 */
class PasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'osrs_username' => 'Pondake',
            'email' => 'player@example.com',
            'password' => Hash::make('Original-pass-1'),
        ]);
    }

    // ---------------------------------------------------------- the request

    #[Test]
    public function asking_for_a_link_sends_one(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * A distinct "no such user" answer would turn this form into a way to
     * find out which addresses have accounts. The message is the same either
     * way, and the test says so rather than leaving it to the comment in the
     * controller.
     */
    #[Test]
    public function an_unknown_address_gets_the_same_answer_as_a_known_one(): void
    {
        Notification::fake();
        $this->user();

        $known = $this->post('/forgot-password', ['email' => 'player@example.com'])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com'])->getSession()->get('status');

        $this->assertSame($known, $unknown);
        $this->assertNotEmpty($known);
    }

    #[Test]
    public function a_malformed_address_is_refused(): void
    {
        $this->post('/forgot-password', ['email' => 'not-an-address'])->assertSessionHasErrors('email');
    }

    // ------------------------------------------------------------ the reset

    #[Test]
    public function the_link_from_the_email_sets_a_new_password(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Brand-new-pass-9',
            'password_confirmation' => 'Brand-new-pass-9',
        ])->assertSessionHasNoErrors()->assertRedirect('/login');

        $this->assertTrue(Hash::check('Brand-new-pass-9', $user->fresh()->password));
        $this->assertFalse(Hash::check('Original-pass-1', $user->fresh()->password));
    }

    /** A token is good once. Otherwise a forwarded email is a spare key. */
    #[Test]
    public function the_same_token_cannot_be_used_twice(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $reset = fn (string $password) => $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $reset('Brand-new-pass-9')->assertSessionHasNoErrors();
        $reset('Someone-elses-9')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('Brand-new-pass-9', $user->fresh()->password));
    }

    #[Test]
    public function a_weak_new_password_is_refused(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Original-pass-1', $user->fresh()->password));
    }

    // ------------------------------------------------------- what it looks like

    /**
     * The mail wears the app's colours.
     *
     * Asserted on the rendered HTML because the wiring is what breaks: the
     * theme is chosen by one line in config/mail.php, and losing it does not
     * fail anything — the mail simply goes out in Laravel's stock zinc again,
     * which nobody notices until a user forwards a screenshot.
     */
    #[Test]
    public function the_mail_is_rendered_in_the_apps_own_theme(): void
    {
        $user = $this->user();

        $html = (string) (new ResetPassword('a-token'))->toMail($user)->render();

        // The brand gold, on the one button the mail has.
        $this->assertStringContainsString('#d4a33e', $html);
        // Parchment, not Laravel's cold grey page.
        $this->assertStringContainsString('#efe9df', $html);
        // And none of the stock zinc it replaced.
        $this->assertStringNotContainsString('#18181b', $html);
    }

    #[Test]
    public function the_mail_carries_a_link_that_actually_works(): void
    {
        $user = $this->user();

        $html = (string) (new ResetPassword('a-token'))->toMail($user)->render();

        $this->assertStringContainsString('reset-password/a-token', $html);
        $this->assertStringContainsString(urlencode($user->email), $html);
    }
}
