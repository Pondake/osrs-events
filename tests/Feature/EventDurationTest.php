<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\EventDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * How long a new event is pre-filled to run for.
 *
 * The reason this is a duration and not a day count: a month has to mean a
 * calendar month. An event starting on 31 January and running "1m" should end
 * on 28 February, not on 2 March — and flattening the unit to 30 days at save
 * time throws away the only thing that can tell you which is right.
 *
 * The awkward dates below are shared with tests/js/duration.test.js on
 * purpose. Two implementations of a calendar in two languages is exactly
 * where they drift, and the drift is invisible until somebody's event ends on
 * the wrong day.
 */
class EventDurationTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------- the parser

    #[Test]
    public function it_reads_the_short_forms(): void
    {
        $this->assertSame(['count' => 10, 'unit' => 'days'], EventDuration::parse('10d'));
        $this->assertSame(['count' => 2, 'unit' => 'weeks'], EventDuration::parse('2w'));
        $this->assertSame(['count' => 1, 'unit' => 'months'], EventDuration::parse('1m'));
    }

    /** The setting held a plain integer before short forms existed. */
    #[Test]
    public function a_bare_number_still_means_days(): void
    {
        $this->assertSame(['count' => 14, 'unit' => 'days'], EventDuration::parse('14'));
    }

    #[Test]
    public function it_is_forgiving_about_case_and_spacing(): void
    {
        $this->assertSame(['count' => 2, 'unit' => 'weeks'], EventDuration::parse('  2W '));
    }

    #[Test]
    public function it_refuses_what_is_not_a_duration(): void
    {
        foreach (['', null, 'soon', '2y', '-3d', '0d', '2 weeks', '1.5m'] as $spec) {
            $this->assertNull(EventDuration::parse($spec), var_export($spec, true));
        }
    }

    /**
     * A ceiling per unit rather than one number: 52 weeks and 12 months are
     * both about a year, and 365 weeks is a typo nobody notices until the
     * standings never close.
     */
    #[Test]
    public function it_caps_each_unit_at_about_a_year(): void
    {
        $this->assertTrue(EventDuration::isValid('365d'));
        $this->assertFalse(EventDuration::isValid('366d'));

        $this->assertTrue(EventDuration::isValid('52w'));
        $this->assertFalse(EventDuration::isValid('53w'));

        $this->assertTrue(EventDuration::isValid('12m'));
        $this->assertFalse(EventDuration::isValid('13m'));
    }

    // ------------------------------------------------------- the arithmetic

    #[Test]
    public function days_and_weeks_are_plain_addition(): void
    {
        $start = Carbon::parse('2026-03-01');

        $this->assertSame('2026-03-11', EventDuration::endFrom($start, '10d')->toDateString());
        $this->assertSame('2026-03-15', EventDuration::endFrom($start, '2w')->toDateString());
    }

    /** The whole reason the unit is stored. */
    #[Test]
    public function a_month_means_a_calendar_month(): void
    {
        $this->assertSame(
            '2026-02-01',
            EventDuration::endFrom(Carbon::parse('2026-01-01'), '1m')->toDateString(),
        );

        // February is shorter, so 28 days.
        $this->assertSame(
            '2026-03-01',
            EventDuration::endFrom(Carbon::parse('2026-02-01'), '1m')->toDateString(),
        );
    }

    /**
     * The case that makes a fixed 30 wrong: there is no 31 February, and
     * rolling into March is not what "a month" means to anybody.
     */
    #[Test]
    public function the_end_of_a_long_month_lands_on_the_end_of_a_short_one(): void
    {
        $this->assertSame(
            '2026-02-28',
            EventDuration::endFrom(Carbon::parse('2026-01-31'), '1m')->toDateString(),
        );

        // And a leap year gives the extra day rather than being hard-coded.
        $this->assertSame(
            '2028-02-29',
            EventDuration::endFrom(Carbon::parse('2028-01-31'), '1m')->toDateString(),
        );
    }

    #[Test]
    public function nonsense_falls_back_to_the_default_rather_than_erroring(): void
    {
        $this->assertSame(
            EventDuration::endFrom(Carbon::parse('2026-03-01'), EventDuration::DEFAULT)->toDateString(),
            EventDuration::endFrom(Carbon::parse('2026-03-01'), 'nonsense')->toDateString(),
        );
    }

    // ---------------------------------------------------------- the setting

    #[Test]
    public function an_admin_can_save_a_short_form(): void
    {
        $this->actingAs($this->admin())->put('/admin/site', $this->settings(['default_event_duration' => '1m']))
            ->assertSessionHasNoErrors();

        $this->assertSame('1m', Setting::get('default_event_duration'));
    }

    #[Test]
    public function a_duration_that_makes_no_sense_is_refused(): void
    {
        $this->actingAs($this->admin())->put('/admin/site', $this->settings(['default_event_duration' => '2y']))
            ->assertSessionHasErrors('default_event_duration');
    }

    /** The create form reads it off the shared props. */
    #[Test]
    public function the_setting_reaches_the_page_that_uses_it(): void
    {
        Setting::set('default_event_duration', '1m');

        $site = $this->actingAs($this->admin())->get('/events')->viewData('page')['props']['site'];

        $this->assertSame('1m', $site['defaultEventDuration']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    /** Every field the settings form posts, so one can be varied at a time. */
    private function settings(array $overrides = []): array
    {
        return [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_dice_roll_limit' => null,
            'default_event_duration' => '2w',
            'kofi_url' => 'https://ko-fi.com/pondake',
            'announcement' => null,
            'announcement_type' => 'info',
            'discord_webhooks_enabled' => false,
            'site_lock_enabled' => false,
            'site_lock_password' => '',
            ...$overrides,
        ];
    }
}
