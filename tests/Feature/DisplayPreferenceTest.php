<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DisplayPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The display catalogue, and the one thing about it that is easy to get wrong.
 *
 * `display_preferences` is null until somebody touches the settings page, and
 * null has to keep meaning "never chosen" rather than "off" — otherwise every
 * account that predates a setting silently opts out of it.
 */
class DisplayPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_account_that_never_chose_gets_every_default(): void
    {
        $this->assertSame(DisplayPreference::ALL, DisplayPreference::resolve(null));
    }

    /**
     * The movement switches are on and the override is off, and those two
     * defaults point in opposite directions on purpose: somebody who has
     * never seen the animation cannot know to turn it on, while a stated
     * accessibility preference wins until its owner says otherwise.
     */
    #[Test]
    public function the_override_is_the_one_setting_that_defaults_to_off(): void
    {
        $resolved = DisplayPreference::resolve(null);

        foreach (DisplayPreference::MOVEMENT as $key) {
            $this->assertTrue($resolved[$key], "{$key} must default to on.");
        }

        $this->assertFalse($resolved[DisplayPreference::PLAY_WHEN_REDUCED]);
        $this->assertNotContains(DisplayPreference::PLAY_WHEN_REDUCED, DisplayPreference::MOVEMENT);
    }

    /**
     * The reason the override is its own key rather than OWN_MOVES being true.
     *
     * The update action writes the whole list at once, so if "stored true"
     * counted as consent to overrule reduced motion, then switching *other*
     * people's moves off would force your own animations back on against an
     * accessibility preference you never revisited.
     */
    #[Test]
    public function turning_another_switch_off_does_not_grant_the_override(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/animations', [
            'preferences' => [
                DisplayPreference::OWN_MOVES => true,
                DisplayPreference::OTHER_MOVES => false,
            ],
        ])->assertRedirect();

        $resolved = DisplayPreference::resolve($user->fresh()->display_preferences);

        $this->assertTrue($resolved[DisplayPreference::OWN_MOVES]);
        $this->assertFalse($resolved[DisplayPreference::OTHER_MOVES]);
        $this->assertFalse($resolved[DisplayPreference::PLAY_WHEN_REDUCED]);
    }

    #[Test]
    public function the_override_is_stored_when_it_is_asked_for(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/animations', [
            'preferences' => [
                DisplayPreference::OWN_MOVES => true,
                DisplayPreference::OTHER_MOVES => true,
                DisplayPreference::PLAY_WHEN_REDUCED => true,
            ],
        ])->assertRedirect();

        $this->assertTrue(
            DisplayPreference::resolve($user->fresh()->display_preferences)[DisplayPreference::PLAY_WHEN_REDUCED],
        );
    }

    /** A stray key is dropped rather than stored — the catalogue is the whitelist. */
    #[Test]
    public function a_key_outside_the_catalogue_is_not_stored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/animations', [
            'preferences' => [DisplayPreference::OWN_MOVES => false, 'animate_the_cat' => true],
        ])->assertRedirect();

        $this->assertArrayNotHasKey('animate_the_cat', $user->fresh()->display_preferences);
    }

    /** The board reads this off the shared auth payload, so it has to be there. */
    #[Test]
    public function the_shared_auth_payload_carries_every_display_setting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/animations')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.display', DisplayPreference::ALL)
                ->where('keys', DisplayPreference::MOVEMENT)
                ->where('overrideKey', DisplayPreference::PLAY_WHEN_REDUCED));
    }
}
