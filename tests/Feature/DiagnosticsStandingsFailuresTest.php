<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The one part of the diagnostics page that reaches somebody else — see
 * DiagnosticsController's own class docs for why these three actions are
 * guarded differently from the four that came before them.
 *
 * WebPushService is swapped for a recorder, same technique as
 * PushNotificationTest: the interesting rules here are grouping and the
 * audit trail, not encryption.
 */
class DiagnosticsStandingsFailuresTest extends TestCase
{
    use RefreshDatabase;

    private RecordingWebPush $push;

    protected function setUp(): void
    {
        parent::setUp();

        $this->push = new RecordingWebPush;
        $this->app->instance(WebPushService::class, $this->push);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email' => 'admin-'.uniqid().'@example.test']);
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $user;
    }

    private function race(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Skill of the Month',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeek(),
        ], $attributes));
    }

    #[Test]
    public function failing_standings_are_grouped_by_account_not_by_row(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Wrongname']);
        $eventA = $this->race(['title' => 'Race A']);
        $eventB = $this->race(['title' => 'Race B']);

        EventStanding::create(['event_id' => $eventA->id, 'user_id' => $player->id, 'username' => 'Wrongname', 'sync_error' => 'not_tracked']);
        EventStanding::create(['event_id' => $eventB->id, 'user_id' => $player->id, 'username' => 'Wrongname', 'sync_error' => 'not_tracked']);

        $data = $this->actingAs($this->admin())
            ->getJson('/admin/diagnostics/standings')
            ->assertOk()
            ->json();

        $this->assertCount(1, $data['users']);
        $this->assertSame($player->id, $data['users'][0]['id']);
        $this->assertCount(2, $data['users'][0]['events']);
    }

    /** A row whose account no longer exists is still counted, but offers no actions. */
    #[Test]
    public function a_row_with_no_account_is_listed_with_no_actions(): void
    {
        $event = $this->race();
        EventStanding::create(['event_id' => $event->id, 'user_id' => null, 'username' => 'GhostName', 'sync_error' => 'not_tracked']);

        $data = $this->actingAs($this->admin())
            ->getJson('/admin/diagnostics/standings')
            ->assertOk()
            ->json();

        $this->assertCount(1, $data['users']);
        $this->assertNull($data['users'][0]['id']);
    }

    #[Test]
    public function synced_standings_are_not_listed_as_failing(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Fine']);
        $event = $this->race();
        EventStanding::create(['event_id' => $event->id, 'user_id' => $player->id, 'username' => 'Fine', 'gained' => 100, 'synced_at' => now(), 'sync_error' => null]);

        $data = $this->actingAs($this->admin())->getJson('/admin/diagnostics/standings')->assertOk()->json();

        $this->assertSame([], $data['users']);
    }

    #[Test]
    public function nudging_sends_a_push_and_logs_it(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Wrongname']);
        PushSubscription::create([
            'user_id' => $player->id,
            'endpoint' => 'https://push.example.test/'.$player->id,
            'public_key' => 'p256dh-'.$player->id,
            'auth_token' => 'auth-'.$player->id,
        ]);
        $event = $this->race();
        EventStanding::create(['event_id' => $event->id, 'user_id' => $player->id, 'username' => 'Wrongname', 'sync_error' => 'not_tracked']);

        $this->actingAs($this->admin())
            ->post("/admin/diagnostics/standings/{$player->id}/nudge")
            ->assertRedirect();

        $this->assertCount(1, $this->push->sent);
        $this->assertSame([$player->id], $this->push->sent[0]['users']);
        $this->assertSame(1, AuditLog::where('action', 'diagnostics.osrs_nudge_sent')->where('target_id', $player->id)->count());
    }

    /** Nobody to nudge about — there's no failing standing for this account. */
    #[Test]
    public function nudging_an_account_with_no_failure_is_refused(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Fine']);

        $this->actingAs($this->admin())
            ->post("/admin/diagnostics/standings/{$player->id}/nudge")
            ->assertNotFound();

        $this->assertSame([], $this->push->sent);
    }

    #[Test]
    public function resetting_the_username_clears_it_and_the_verification_flag(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Wrongname', 'osrs_verified_at' => now()]);
        $event = $this->race();
        EventStanding::create(['event_id' => $event->id, 'user_id' => $player->id, 'username' => 'Wrongname', 'sync_error' => 'not_tracked']);

        $this->actingAs($this->admin())
            ->delete("/admin/diagnostics/standings/{$player->id}/username")
            ->assertRedirect();

        $fresh = $player->fresh();
        $this->assertNull($fresh->osrs_username);
        $this->assertNull($fresh->osrs_verified_at);
        $this->assertSame(1, AuditLog::where('action', 'diagnostics.osrs_username_reset')->where('target_id', $player->id)->count());
    }

    #[Test]
    public function a_non_admin_cannot_reach_any_of_the_three_routes(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Wrongname']);
        $stranger = User::factory()->create(['osrs_username' => 'Stranger']);

        $this->actingAs($stranger)->getJson('/admin/diagnostics/standings')->assertForbidden();
        $this->actingAs($stranger)->post("/admin/diagnostics/standings/{$player->id}/nudge")->assertForbidden();
        $this->actingAs($stranger)->delete("/admin/diagnostics/standings/{$player->id}/username")->assertForbidden();
    }
}
