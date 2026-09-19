<?php

namespace Tests\Feature;

use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the first-run tour is told about the RuneLite plugin.
 *
 * The step list itself is built in the browser, but from exactly these props:
 * the site-wide mode decides whether the step exists at all, and the per-user
 * flags decide what it offers. Asserting them here is the server half of
 * "the intro follows the setting".
 */
class OnboardingPluginStepTest extends TestCase
{
    use RefreshDatabase;

    private function newcomer(array $attributes = []): User
    {
        return User::factory()->create([
            'osrs_username' => 'Pondake',
            'onboarding_completed_at' => null,
            ...$attributes,
        ]);
    }

    private function mode(string $mode): void
    {
        Setting::set('runelite_plugin_mode', $mode);
    }

    #[Test]
    public function while_the_plugin_is_off_the_tour_is_told_so_and_can_create_nothing(): void
    {
        $this->mode('off');
        $user = $this->newcomer();
        PluginToken::issueFor($user);

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('site.runelitePluginMode', 'off')
            ->where('auth.user.hasPluginCode', false)
            ->where('auth.user.needsOsrsProof', false));

        $this->actingAs($user)->post('/settings/runelite/code')->assertNotFound();
    }

    #[Test]
    public function in_testing_the_tour_offers_the_step_and_proof_is_not_yet_a_condition(): void
    {
        $this->mode('testing');
        $user = $this->newcomer();

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('site.runelitePluginMode', 'testing')
            ->where('auth.user.hasPluginCode', false)
            ->where('auth.user.needsOsrsProof', false));

        PluginToken::issueFor($user);

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('auth.user.hasPluginCode', true));
    }

    #[Test]
    public function live_tells_an_unproven_name_apart_from_a_proven_one(): void
    {
        $this->mode('live');

        $this->actingAs($this->newcomer())->get('/events')->assertInertia(fn ($page) => $page
            ->where('site.runelitePluginMode', 'live')
            ->where('auth.user.osrsProven', false)
            ->where('auth.user.needsOsrsProof', true));

        $this->actingAs($this->newcomer(['osrs_username' => 'Proven', 'osrs_proven_at' => now()]))
            ->get('/events')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.osrsProven', true)
                ->where('auth.user.needsOsrsProof', false));
    }

    #[Test]
    public function an_unproven_name_can_still_create_its_code_from_the_tour(): void
    {
        $this->mode('live');
        $user = $this->newcomer();

        $this->actingAs($user)->from('/events')->post('/settings/runelite/code')->assertRedirect('/events');

        $this->assertSame(1, PluginToken::where('user_id', $user->id)->count());
    }

    #[Test]
    public function the_new_code_reaches_the_page_the_tour_is_open_on_exactly_once(): void
    {
        $this->mode('testing');
        $user = $this->newcomer();

        $this->actingAs($user)->from('/events')->post('/settings/runelite/code');

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('flash.pluginCode', fn ($code) => PluginToken::findByPlain($code)?->user_id === $user->id)
            ->where('auth.user.hasPluginCode', true));

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('flash.pluginCode', null));
    }

    #[Test]
    public function the_code_flag_is_not_computed_once_the_tour_is_finished(): void
    {
        $this->mode('live');
        $user = $this->newcomer(['onboarding_completed_at' => now()]);
        PluginToken::issueFor($user);

        $this->actingAs($user)->get('/events')->assertInertia(fn ($page) => $page
            ->where('auth.user.hasPluginCode', false));
    }
}
