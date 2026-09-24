<?php

namespace Tests\Feature;

use App\Models\PluginCompletion;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\User;
use App\Services\RunelitePluginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The live status on /settings/runelite: a per-account stream, not an
 * EventChannel — this data must never be shared between viewers.
 */
class RunelitePluginStatusTest extends TestCase
{
    use RefreshDatabase;

    private function mode(string $mode): void
    {
        Setting::set('runelite_plugin_mode', $mode);
    }

    private function service(): RunelitePluginService
    {
        return app(RunelitePluginService::class);
    }

    #[Test]
    public function the_stream_does_not_exist_while_the_plugin_is_off(): void
    {
        $this->mode('off');

        $this->actingAs(User::factory()->create())
            ->get('/settings/runelite/stream')
            ->assertNotFound();
    }

    #[Test]
    public function the_stream_requires_auth(): void
    {
        $this->mode('testing');

        $this->get('/settings/runelite/stream')->assertRedirect('/login');
    }

    #[Test]
    public function show_props_include_the_status(): void
    {
        $this->mode('testing');
        $user = User::factory()->create();
        PluginToken::issueFor($user);

        $this->actingAs($user)->get('/settings/runelite')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/RunelitePlugin')
                ->has('status')
                ->where('status.connection.state', 'never')
                ->where('status.watching.count', 0)
                ->where('status.reports', []));
    }

    #[Test]
    public function the_fingerprint_is_stable_when_nothing_displayed_changed(): void
    {
        $user = User::factory()->create();
        $token = PluginToken::issueFor($user);
        $user = $user->fresh();

        $before = $this->service()->statusFingerprint($user);

        // Touching an unrelated column must not change what the fingerprint
        // reports — the same rule EventChannel::fingerprint() follows.
        PluginToken::where('user_id', $user->id)->update(['hint' => 'zzzz']);

        $this->assertSame($before, $this->service()->statusFingerprint($user));
    }

    #[Test]
    public function the_payload_reflects_a_new_plugin_completion(): void
    {
        $user = User::factory()->create();

        $before = $this->service()->statusFingerprint($user);

        PluginCompletion::create([
            'user_id' => $user->id,
            'client_event_id' => 'evt-1',
            'kind' => 'item',
            'name' => 'Dragon bones',
            'quantity' => 1,
            'rsn' => $user->osrs_username,
            'occurred_at' => Carbon::now(),
            'claims' => [
                [
                    'event_id' => 'e1',
                    'event_title' => 'Bones bingo',
                    'kind' => 'bingo_square',
                    'id' => 's1',
                    'position' => 0,
                    'label' => 'Get dragon bones',
                    'name' => 'Dragon bones',
                    'match' => 'dragon bones',
                    'status' => 'APPROVED',
                ],
            ],
        ]);

        $this->assertNotSame($before, $this->service()->statusFingerprint($user));

        $status = $this->service()->status($user);

        $this->assertCount(1, $status['reports']);
        $this->assertSame('Dragon bones', $status['reports'][0]['name']);
        $this->assertSame('Bones bingo', $status['reports'][0]['claims'][0]['eventTitle']);
        $this->assertSame('APPROVED', $status['reports'][0]['claims'][0]['status']);
    }

    #[Test]
    public function connection_state_reflects_recency_of_last_use(): void
    {
        $user = User::factory()->create();
        PluginToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => 'hash',
            'hint' => 'abcd',
            'last_used_at' => null,
        ]);

        $this->assertSame('never', $this->service()->status($user)['connection']['state']);

        PluginToken::where('user_id', $user->id)->update(['last_used_at' => Carbon::now()->subMinutes(2)]);
        $this->assertSame('connected', $this->service()->status($user->fresh())['connection']['state']);

        PluginToken::where('user_id', $user->id)->update(['last_used_at' => Carbon::now()->subMinutes(20)]);
        $this->assertSame('stale', $this->service()->status($user->fresh())['connection']['state']);
    }

    #[Test]
    public function no_token_reports_none(): void
    {
        $user = User::factory()->create();

        $this->assertSame('none', $this->service()->status($user)['connection']['state']);
    }
}
