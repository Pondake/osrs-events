<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every account needs an OSRS username, enforced at all three doors: the
 * registration form, the post-login gate for accounts the form never touched
 * (Discord logins, and anything created before the field existed), and
 * profile settings.
 */
class OsrsUsernameRequirementTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFound(string $displayName = 'Pondake'): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['displayName' => $displayName])]);
    }

    private function fakeMissing(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['code' => 'PLAYER_NOT_FOUND'], 404)]);
    }

    /** @return array<string, array{0: string}> */
    private function registration(array $overrides = []): array
    {
        return [
            'nickname' => 'Tester',
            'osrs_username' => 'Pondake',
            'email' => 'tester@example.com',
            'password' => 'TestPass123',
            'password_confirmation' => 'TestPass123',
            ...$overrides,
        ];
    }

    // ----------------------------------------------------- registration

    #[Test]
    public function registration_requires_an_osrs_username(): void
    {
        Http::fake();

        $this->post('/register', $this->registration(['osrs_username' => '']))
            ->assertSessionHasErrors('osrs_username');

        $this->assertSame(0, User::count());
    }

    #[Test]
    public function registration_stores_the_verified_name(): void
    {
        $this->fakeFound('Pondake');

        $this->post('/register', $this->registration(['osrs_username' => 'pondake']))
            ->assertRedirect();

        $user = User::firstOrFail();
        $this->assertSame('Pondake', $user->osrs_username);
        $this->assertNotNull($user->osrs_verified_at);
    }

    /**
     * The whole reason the check is a warning: Wise Old Man only knows
     * accounts somebody has looked up there, so a genuine newcomer 404s.
     * Refusing the signup would turn that into a closed door.
     */
    #[Test]
    public function registration_succeeds_even_when_wise_old_man_has_never_heard_of_the_account(): void
    {
        $this->fakeMissing();

        $this->post('/register', $this->registration(['osrs_username' => 'Brand New']))
            ->assertRedirect();

        $this->assertAuthenticated();
        $user = User::firstOrFail();
        $this->assertSame('Brand New', $user->osrs_username);
        $this->assertNull($user->osrs_verified_at);
    }

    #[Test]
    public function a_failing_lookup_never_fails_the_signup(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response('', 500)]);

        $this->post('/register', $this->registration())->assertRedirect();

        $this->assertSame(1, User::count());
        $this->assertAuthenticated();
    }

    // ------------------------------------------------------------- gate

    #[Test]
    public function an_account_without_a_name_is_redirected_to_the_gate(): void
    {
        $user = User::factory()->create([
            'osrs_username' => null,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)->get('/my-events')->assertRedirect('/welcome/osrs-username');
    }

    /**
     * The first-run wizard asks for the name itself, so during that window
     * the standalone page would be a second demand for the same field —
     * arriving, worst of all, the moment the tour finishes.
     */
    #[Test]
    public function browsing_is_allowed_while_the_first_run_wizard_is_still_pending(): void
    {
        $user = User::factory()->create([
            'osrs_username' => null,
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user)->get('/my-events')->assertOk();
    }

    /**
     * The tour's own endpoints sit inside the gated route group, so before
     * they were exempted a brand-new Discord account — which by definition
     * has no OSRS name yet — had its completion POST redirected to the gate
     * instead of saved. Finishing the tour did nothing, and it reopened on
     * every single navigation, forever.
     */
    #[Test]
    public function finishing_the_tour_persists_even_without_a_name(): void
    {
        $user = User::factory()->create([
            'osrs_username' => null,
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user)->post('/onboarding/complete');

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    /**
     * The other half of that relaxation, and the half that keeps it honest:
     * reads are let through, writes are not. Without this, dismissing the
     * wizard would leave an account able to join, roll and claim with no
     * name at all — scoring nothing, silently.
     */
    #[Test]
    public function writing_is_still_gated_while_the_wizard_is_pending(): void
    {
        $user = User::factory()->create([
            'osrs_username' => null,
            'onboarding_completed_at' => null,
        ]);

        $event = Event::create([
            'title' => 'Open Board',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->actingAs($user)
            ->post("/events/{$event->id}/join")
            ->assertRedirect('/welcome/osrs-username');
    }

    #[Test]
    public function the_gate_itself_stays_reachable(): void
    {
        $user = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($user)->get('/welcome/osrs-username')->assertOk();
    }

    /**
     * Being unable to sign out of an account you are locked inside would be
     * worse than the missing field.
     */
    #[Test]
    public function logging_out_stays_possible_without_a_name(): void
    {
        $user = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function an_account_with_a_name_passes_straight_through(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $this->actingAs($user)->get('/my-events')->assertOk();
    }

    #[Test]
    public function submitting_the_gate_saves_the_name_and_lets_the_user_in(): void
    {
        $this->fakeFound('Pondake');
        $user = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($user)
            ->post('/welcome/osrs-username', ['osrs_username' => 'pondake'])
            ->assertRedirect();

        $this->assertSame('Pondake', $user->fresh()->osrs_username);
        $this->actingAs($user->fresh())->get('/my-events')->assertOk();
    }

    #[Test]
    public function the_gate_saves_an_unknown_name_and_says_so(): void
    {
        $this->fakeMissing();
        $user = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($user)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Brand New'])
            ->assertRedirect()
            ->assertSessionHas('board-save-error');

        $this->assertSame('Brand New', $user->fresh()->osrs_username);
    }

    // ------------------------------------------------------------- rule

    /** @return array<string, array{0: string, 1: bool}> */
    public static function names(): array
    {
        return [
            'plain' => ['Pondake', true],
            'with a space' => ['Lynx Titan', true],
            'with an underscore' => ['Some_Name', true],
            'with a hyphen' => ['Some-Name', true],
            'digits' => ['B0aty', true],
            'exactly twelve' => ['Abcdefghijkl', true],
            // Surrounding whitespace is trimmed, not rejected — someone
            // pasting a name out of the game or Discord should not be told
            // off for it. Separators inside the name are a different matter.
            'surrounding spaces' => [' Pondake ', true],
            'thirteen characters' => ['Abcdefghijklm', false],
            'empty' => ['', false],
            'only whitespace' => ['   ', false],
            'a dot' => ['some.name', false],
            'a double space' => ['Some  Name', false],
            'a leading underscore' => ['_Pondake', false],
            'a trailing underscore' => ['Pondake_', false],
            'punctuation' => ['Pondake!', false],
        ];
    }

    #[Test]
    #[DataProvider('names')]
    public function it_accepts_only_names_an_osrs_account_could_have(string $name, bool $valid): void
    {
        $this->fakeMissing();
        $user = User::factory()->create(['osrs_username' => null]);

        $response = $this->actingAs($user)->post('/welcome/osrs-username', ['osrs_username' => $name]);

        $valid
            ? $response->assertSessionHasNoErrors()
            : $response->assertSessionHasErrors('osrs_username');
    }

    // ------------------------------------------- coming back where you were

    /**
     * The gate sends you to the name page and then back to what you were
     * doing.
     *
     * Nothing was storing the destination, so `redirect()->intended()` in
     * OsrsUsernameController had nothing to read and always fell back to
     * /events. Reported as: gave the name during setup, went to claim a
     * bingo square, was asked for the name again, and then landed somewhere
     * else entirely.
     */
    #[Test]
    public function it_returns_you_to_the_page_you_were_on(): void
    {
        $this->fakeFound();

        $user = User::factory()->create(['osrs_username' => null, 'onboarding_completed_at' => now()]);

        $this->actingAs($user)->get('/teams')->assertRedirect('/welcome/osrs-username');

        $this->actingAs($user)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Pondake'])
            ->assertRedirect('/teams');
    }

    /**
     * A write cannot be replayed by a redirect, so the page it was made from
     * is the honest destination.
     */
    #[Test]
    public function a_blocked_write_returns_you_to_the_page_it_came_from(): void
    {
        $this->fakeFound();

        $user = User::factory()->create(['osrs_username' => null, 'onboarding_completed_at' => now()]);
        $event = Event::create([
            'title' => 'Clan Bingo',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->actingAs($user)
            ->from("/events/{$event->id}")
            ->post("/events/{$event->id}/join")
            ->assertRedirect('/welcome/osrs-username');

        $this->actingAs($user)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Pondake'])
            ->assertRedirect("/events/{$event->id}");
    }
}
