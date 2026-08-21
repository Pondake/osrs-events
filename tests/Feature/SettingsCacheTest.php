<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Settings, and the deploy-time 500 that lived in how they were cached.
 *
 * DEFAULTS used to be merged INSIDE the cache closure, so the cached value
 * was a snapshot of whichever keys existed when it was written. Add a new
 * setting, deploy, and every read of it was an "Undefined array key" until
 * somebody happened to clear the cache — which duly happened the first time
 * one was added (site_lock_password, /admin/site, 500).
 *
 * The fix is one line and easy to undo by accident, so this is the test that
 * notices.
 */
class SettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    #[Test]
    public function an_untouched_setting_reads_its_default(): void
    {
        $this->assertTrue(Setting::get('registration_open'));
        $this->assertSame('SIZE_7X7', Setting::get('default_board_size'));
    }

    #[Test]
    public function a_stored_value_wins_over_the_default(): void
    {
        Setting::set('default_board_size', 'SIZE_9X9');

        $this->assertSame('SIZE_9X9', Setting::get('default_board_size'));
    }

    /**
     * The regression itself, simulated: a cache warmed before a key existed.
     *
     * Written by hand rather than by adding a fake DEFAULT, because what
     * broke was the *shape* of the cached value — a snapshot of the merged
     * array rather than of the table.
     */
    #[Test]
    public function a_cache_warmed_before_a_setting_existed_still_answers_for_it(): void
    {
        // What the old code would have left behind: a complete-looking
        // settings array from an earlier deploy, missing today's keys.
        Cache::forever('settings.all', ['registration_open' => true]);

        // Every current default still resolves, rather than tripping an
        // undefined-key error the moment somebody reads one.
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $this->assertArrayHasKey($key, Setting::cached(), $key);
        }

        $this->assertSame('SIZE_7X7', Setting::get('default_board_size'));
    }

    #[Test]
    public function writing_a_setting_invalidates_the_cache(): void
    {
        $this->assertTrue(Setting::get('registration_open'));

        Setting::set('registration_open', false);

        $this->assertFalse(Setting::get('registration_open'));
    }

    #[Test]
    public function setting_many_at_once_invalidates_the_cache(): void
    {
        Setting::setMany(['registration_open' => false, 'default_dice_roll_limit' => 5]);

        $this->assertFalse(Setting::get('registration_open'));
        $this->assertSame(5, Setting::get('default_dice_roll_limit'));
    }

    /**
     * get() on a key that is not a setting is a programming error, not a
     * supported state — it answers null rather than inventing a value.
     */
    #[Test]
    public function an_unknown_key_is_null(): void
    {
        $this->assertNull(Setting::get('there_is_no_such_setting'));
    }

    /** Every default is a real value, not a stray null that means "unset". */
    #[Test]
    public function the_defaults_cover_every_setting_the_admin_form_writes(): void
    {
        foreach ([
            'registration_open',
            'default_board_size',
            'default_dice_roll_limit',
            'default_event_duration_days',
            'announcement_type',
            'kofi_url',
            'site_lock_enabled',
        ] as $key) {
            $this->assertArrayHasKey($key, Setting::DEFAULTS, $key);
        }
    }
}
