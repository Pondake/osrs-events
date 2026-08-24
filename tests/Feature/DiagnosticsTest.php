<?php

namespace Tests\Feature;

use App\Models\EventStanding;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\User;
use App\Support\DiagnosticCheck;
use App\Support\ScheduleHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The page that answers "why is nothing happening".
 *
 * What is worth testing here is not that the checks run — it is that they
 * reach the right *verdict* on states that are otherwise indistinguishable
 * from working. A mismatched VAPID pair, a scheduler that has never run, a
 * mailer that reports success and delivers nothing: all three look identical
 * to a healthy install from anywhere else in the app.
 *
 * **Two throwaway keypairs**, generated for these tests and used nowhere else,
 * so a mismatch can be built out of two halves that are each individually
 * valid — which is exactly what makes that failure invisible.
 */
class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private const PAIR_A = [
        'public' => 'BLDTpjptVjuplqRbmgAXGl-39TnmDc0VRDFRdB7PNxB-BJdROpk8vXnmfcCYgfWljNsVm8EQNBoZl_RaR8XyFjo',
        'private' => 'r13Ymth3hSMx2d-yZaTIOrxkD1krfC-nh9A5M8NRlXg',
    ];

    private const PAIR_B = [
        'public' => 'BGdZM25g4pPl5zXYsLCz3sTtdXOCKM4DV0wo8wxzYgmpu2qH8X6fWMeew4lEoENZTXDgkFRvWwFMX5wS1Cv1ia4',
        'private' => 'csDLGVMTeYgcimpUVlprMnO4nkLvRo6YX2uQdwZdBsA',
    ];

    private function admin(): User
    {
        // Unique per call: one test signs in as three different admins to
        // exercise three branches, and a shared address collides on the
        // users.email unique index.
        $user = User::factory()->create(['email' => 'admin-'.uniqid().'@example.test']);
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $user;
    }

    private function withPair(array $pair, ?string $private = null): void
    {
        config([
            'webpush.vapid.subject' => 'mailto:ops@example.test',
            'webpush.vapid.public_key' => $pair['public'],
            'webpush.vapid.private_key' => $private ?? $pair['private'],
        ]);
    }

    /** Find one check by label across every group. */
    private function check(array $groups, string $label): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['checks'] as $check) {
                if ($check['label'] === $label) {
                    return $check;
                }
            }
        }

        return null;
    }

    private function allGroups(): array
    {
        return app(\App\Services\DiagnosticsService::class)->all();
    }

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    #[Test]
    public function the_page_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/diagnostics')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_every_group(): void
    {
        $this->withPair(self::PAIR_A);

        $this->actingAs($this->admin())
            ->get('/admin/diagnostics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Diagnostics')
                ->has('groups', 5));
    }

    /**
     * The page is built to be screenshotted into a chat, so the one thing it
     * must never do is print the key it is describing.
     */
    #[Test]
    public function the_page_never_prints_the_private_key(): void
    {
        $this->withPair(self::PAIR_A);

        $this->actingAs($this->admin())
            ->get('/admin/diagnostics')
            ->assertOk()
            ->assertDontSee(self::PAIR_A['private'])
            ->assertDontSee(self::PAIR_A['public']);
    }

    // -----------------------------------------------------------------
    // The invisible failures
    // -----------------------------------------------------------------

    /**
     * The check nothing else makes. Both halves are individually valid — right
     * length, right shape, accepted by the send library — and every push is
     * accepted by the push service and delivered to nobody.
     */
    #[Test]
    public function a_mismatched_key_pair_is_reported_as_broken(): void
    {
        $this->withPair(self::PAIR_A, self::PAIR_B['private']);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_pair'));

        $this->assertNotNull($check);
        $this->assertSame(DiagnosticCheck::FAIL, $check['status']);
    }

    #[Test]
    public function a_matching_key_pair_passes(): void
    {
        $this->withPair(self::PAIR_A);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_pair'));

        $this->assertSame(DiagnosticCheck::OK, $check['status']);
    }

    /** A bare domain is rejected by Apple with no clue which field was wrong. */
    #[Test]
    public function a_subject_that_is_not_a_mailto_or_url_is_reported(): void
    {
        $this->withPair(self::PAIR_A);
        config(['webpush.vapid.subject' => 'osrs-events.com']);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_subject'));

        $this->assertSame(DiagnosticCheck::FAIL, $check['status']);
    }

    /**
     * It passes every format check — it *is* a mailto: URL — and push services
     * use this address to reach an operator whose app is misbehaving. An
     * address nobody reads turns a warning into a block with no warning.
     */
    #[Test]
    public function the_generators_example_subject_is_flagged_rather_than_passed(): void
    {
        $this->withPair(self::PAIR_A);
        config(['webpush.vapid.subject' => 'mailto:you@example.com']);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_subject'));

        $this->assertSame(DiagnosticCheck::WARN, $check['status']);
    }

    #[Test]
    public function missing_keys_are_reported_without_throwing(): void
    {
        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_keys'));

        $this->assertSame(DiagnosticCheck::FAIL, $check['status']);
    }

    /**
     * After a key change these rows behave exactly like healthy ones — the
     * push service keeps accepting sends to them — so nothing but this names
     * a device that will never receive anything again.
     */
    #[Test]
    public function devices_on_an_old_vapid_key_are_named(): void
    {
        $this->withPair(self::PAIR_A);

        PushSubscription::create([
            'user_id' => User::factory()->create()->id,
            'endpoint' => 'https://push.example.test/old',
            'public_key' => 'k',
            'auth_token' => 'a',
            'vapid_key' => self::PAIR_B['public'],
        ]);

        $check = $this->check($this->allGroups(), trans('diagnostics.push_stale'));

        $this->assertNotNull($check);
        $this->assertSame(DiagnosticCheck::WARN, $check['status']);
    }

    /**
     * The quietest failure in the app: no cron entry means standings never
     * move and five notification categories never fire, while every page
     * renders perfectly and nothing is logged.
     */
    #[Test]
    public function a_scheduler_that_has_never_run_is_reported_as_broken(): void
    {
        $check = $this->check($this->allGroups(), trans('diagnostics.schedule_sweep'));

        $this->assertSame(DiagnosticCheck::FAIL, $check['status']);
    }

    #[Test]
    public function a_recent_scheduler_run_passes(): void
    {
        ScheduleHeartbeat::record('push:sweep');

        $check = $this->check($this->allGroups(), trans('diagnostics.schedule_sweep'));

        $this->assertSame(DiagnosticCheck::OK, $check['status']);
    }

    #[Test]
    public function a_stale_scheduler_run_is_reported_as_broken(): void
    {
        ScheduleHeartbeat::record('push:sweep');
        $this->travel(3)->hours();

        $check = $this->check($this->allGroups(), trans('diagnostics.schedule_sweep'));

        $this->assertSame(DiagnosticCheck::FAIL, $check['status']);
    }

    /**
     * `log` is the dangerous one precisely because it is not an error: it
     * writes the message to a file and tells the user their reset link is on
     * its way.
     */
    #[Test]
    public function the_log_mailer_is_flagged_rather_than_passed(): void
    {
        config(['mail.default' => 'log']);

        $check = $this->check($this->allGroups(), trans('diagnostics.mail_driver'));

        $this->assertSame(DiagnosticCheck::WARN, $check['status']);
    }

    #[Test]
    public function entrants_that_cannot_be_measured_are_surfaced(): void
    {
        $event = \App\Models\Event::create(['title' => 'Race', 'type' => 'SKILL_RACE', 'metric' => 'mining', 'mode' => 'SOLO']);

        EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Not A Player',
            'sync_error' => 'not found',
        ]);

        $check = $this->check($this->allGroups(), trans('diagnostics.wom_standings'));

        $this->assertSame(DiagnosticCheck::WARN, $check['status']);
    }

    // -----------------------------------------------------------------
    // The buttons
    // -----------------------------------------------------------------

    #[Test]
    public function a_test_push_with_no_devices_says_so_instead_of_failing(): void
    {
        $this->withPair(self::PAIR_A);

        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/push', ['category' => 'claim_reviewed'])
            ->assertRedirect()
            ->assertSessionHas('board-save-error');
    }

    #[Test]
    public function a_test_push_rejects_a_category_that_does_not_exist(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/push', ['category' => 'not_a_category'])
            ->assertSessionHasErrors('category');
    }

    /** Discord login never asks for an address, so this is a normal state. */
    #[Test]
    public function a_test_mail_to_an_account_with_no_address_explains_itself(): void
    {
        $user = User::factory()->create(['email' => null]);
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($user)
            ->post('/admin/diagnostics/mail')
            ->assertRedirect()
            ->assertSessionHas('board-save-error');
    }

    #[Test]
    public function a_test_mail_goes_to_the_admins_own_address_and_nowhere_else(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/diagnostics/mail')->assertSessionHas('board-save');

        // Read from the array transport phpunit.xml already configures rather
        // than Mail::fake(): this sends a raw message, not a Mailable, and the
        // fake's assertions are built around Mailable classes.
        $messages = Mail::getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertSame(
            [$admin->email],
            array_map(fn ($address) => $address->getAddress(), $messages[0]->getOriginalMessage()->getTo()),
        );
    }

    /**
     * Three outcomes, not two, and three tests rather than one.
     *
     * Not for tidiness: `Http::fake()` **merges** stub callbacks rather than
     * replacing them, so re-faking the same URL pattern inside one test leaves
     * the first stub answering every later call. Written as one test this
     * passed while only ever exercising the 200 branch.
     */
    #[Test]
    public function a_known_player_reports_the_api_as_reachable(): void
    {
        Http::fake(['*/players/*' => Http::response(['displayName' => 'Zezima'], 200)]);

        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/wom', ['username' => 'Zezima'])
            ->assertSessionHas('board-save');
    }

    /**
     * A player their API has never heard of is a *working* API. Reporting it
     * as a failure would send somebody debugging their own server.
     */
    #[Test]
    public function an_unknown_player_is_not_reported_as_a_failure(): void
    {
        Http::fake(['*/players/*' => Http::response([], 404)]);

        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/wom', ['username' => 'Nobody'])
            ->assertSessionHas('board-save')
            ->assertSessionMissing('board-save-error');
    }

    #[Test]
    public function an_api_that_errors_is_reported_as_unreachable(): void
    {
        Http::fake(['*/players/*' => Http::response([], 500)]);

        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/wom', ['username' => 'Zezima'])
            ->assertSessionHas('board-save-error');
    }

    /** Dry run only: a button that buzzes thirty phones is not a diagnostic. */
    #[Test]
    public function the_sweep_button_returns_output_and_sends_nothing(): void
    {
        $this->withPair(self::PAIR_A);

        $this->actingAs($this->admin())
            ->post('/admin/diagnostics/sweep')
            ->assertRedirect()
            ->assertSessionHas('sweepOutput');

        // Nothing was marked as claimed, so the real sweep that follows is
        // not suppressed by the rehearsal.
        $this->assertNull(cache('push-sweep:start:anything'));
    }

    #[Test]
    public function the_command_and_the_page_agree(): void
    {
        $this->withPair(self::PAIR_A, self::PAIR_B['private']);

        // Same service, so a mismatch that fails on the page must also fail
        // the command — which is what makes it usable as a deploy gate.
        $this->artisan('push:doctor')->assertExitCode(1);
    }
}
