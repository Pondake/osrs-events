<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The admin search box is fed by Setting::searchIndex(), built from
 * Setting::DEFAULTS. That only works if every key has a label under
 * `admin.setting_<key>` — which is also what the form field renders — so
 * this is the test that notices a setting added without one.
 */
class SettingsSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_setting_has_a_label(): void
    {
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $this->assertNotSame("admin.setting_{$key}", __("admin.setting_{$key}"), "No label for setting '{$key}'");
        }
    }

    #[Test]
    public function the_index_covers_every_setting_and_carries_no_values(): void
    {
        Setting::set('kofi_url', 'https://ko-fi.com/somebody');

        $index = Setting::searchIndex();

        $this->assertSame(array_keys(Setting::DEFAULTS), array_column($index, 'key'));
        $this->assertSame(['key', 'label', 'description'], array_keys($index[0]));
        $this->assertStringNotContainsString('somebody', json_encode($index));
    }

    #[Test]
    public function admin_pages_share_the_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->has('settingsIndex', count(Setting::DEFAULTS))
                ->where('settingsIndex.0.key', 'registration_open'));
    }

    #[Test]
    public function pages_outside_admin_do_not(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)
            ->get('/events')
            ->assertInertia(fn ($page) => $page->missing('settingsIndex'));
    }
}
