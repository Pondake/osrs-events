<?php

namespace Tests\Feature;

use App\Models\PluginToken;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginTokenTest extends TestCase
{
    use RefreshDatabase;

    private function mode(string $mode): void
    {
        Setting::set('runelite_plugin_mode', $mode);
    }

    #[Test]
    public function the_settings_tab_does_not_exist_while_the_plugin_is_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/runelite')->assertNotFound();
        $this->actingAs($user)->post('/settings/runelite/code')->assertNotFound();
        $this->assertSame(0, PluginToken::count());
    }

    #[Test]
    public function the_mode_is_shared_with_every_page(): void
    {
        $this->mode('testing');

        $this->actingAs(User::factory()->create())
            ->get('/settings/runelite')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/RunelitePlugin')
                ->where('site.runelitePluginMode', 'testing')
                ->where('mode', 'testing')
                ->where('token', null));
    }

    #[Test]
    public function a_created_code_is_shown_once_and_stored_only_as_a_hash(): void
    {
        $this->mode('testing');
        $user = User::factory()->create();

        $this->actingAs($user)->from('/settings/runelite')->post('/settings/runelite/code')->assertRedirect('/settings/runelite');

        $code = null;
        $this->actingAs($user)->get('/settings/runelite')
            ->assertInertia(function ($page) use (&$code) {
                $code = $page->toArray()['props']['newCode'];
                $page->where('token.hint', substr($code, -4));
            });

        $this->assertStringStartsWith(PluginToken::PREFIX, $code);
        $this->assertDatabaseMissing('plugin_tokens', ['token_hash' => $code]);
        $this->assertTrue(PluginToken::findByPlain($code)->user->is($user));

        $this->actingAs($user)->get('/settings/runelite')
            ->assertInertia(fn ($page) => $page->where('newCode', null));
    }

    #[Test]
    public function replacing_a_code_invalidates_the_old_one(): void
    {
        $this->mode('live');
        $user = User::factory()->create();

        $old = PluginToken::issueFor($user);
        $new = PluginToken::issueFor($user);

        $this->assertNull(PluginToken::findByPlain($old));
        $this->assertNotNull(PluginToken::findByPlain($new));
        $this->assertSame(1, PluginToken::where('user_id', $user->id)->count());
    }

    #[Test]
    public function a_code_can_be_revoked(): void
    {
        $this->mode('live');
        $user = User::factory()->create();
        $code = PluginToken::issueFor($user);

        $this->actingAs($user)->delete('/settings/runelite/code')->assertRedirect();

        $this->assertNull(PluginToken::findByPlain($code));
    }

    #[Test]
    public function a_code_without_the_prefix_never_matches(): void
    {
        $user = User::factory()->create();
        $code = PluginToken::issueFor($user);

        $this->assertNull(PluginToken::findByPlain(substr($code, strlen(PluginToken::PREFIX))));
        $this->assertNull(PluginToken::findByPlain(''));
    }

    #[Test]
    public function an_admin_can_move_the_plugin_to_testing(): void
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->put('/admin/site', [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_event_duration' => '2w',
            'default_dice_roll_limit' => null,
            'kofi_url' => 'https://ko-fi.com/pondake',
            'discord_invite_url' => null,
            'announcement' => null,
            'announcement_type' => 'info',
            'announcement_public' => false,
            'discord_webhooks_enabled' => false,
            'site_lock_enabled' => false,
            'site_lock_password' => '',
            'admin_lockdown_enabled' => false,
            'runelite_plugin_mode' => 'testing',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('testing', Setting::get('runelite_plugin_mode'));
    }

    #[Test]
    public function only_an_admin_can_change_the_mode(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/site', ['runelite_plugin_mode' => 'live']);

        $this->assertSame('off', Setting::get('runelite_plugin_mode'));
    }
}
