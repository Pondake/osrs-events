<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OsrsIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Setting an OSRS username, which is a save plus a check plus a verdict.
 *
 * The rule the whole feature rests on: **the name is always stored**. Wise Old
 * Man only knows accounts somebody has looked up there at least once, so a
 * real newcomer legitimately 404s, and refusing the name would lock out
 * exactly the people the app is for.
 */
class OsrsIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PLAYER_URL = 'api.wiseoldman.net/v2/players/*';

    private function identity(): OsrsIdentityService
    {
        return app(OsrsIdentityService::class);
    }

    private function fakeFound(string $displayName = 'Main Sample'): void
    {
        Http::fake([self::PLAYER_URL => Http::response(['displayName' => $displayName])]);
    }

    private function fakeMissing(): void
    {
        Http::fake([self::PLAYER_URL => Http::response(['code' => 'PLAYER_NOT_FOUND'], 404)]);
    }

    /**
     * A name another account carries is a fact to report, never a refusal —
     * see takenByAnother(). Underscores, hyphens and spaces are one
     * character to the game, so they are one name here too.
     */
    #[Test]
    public function a_name_another_account_carries_is_reported_as_taken(): void
    {
        User::factory()->create(['osrs_username' => 'Main Sample']);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->assertTrue($this->identity()->takenByAnother($newcomer, 'main sample'));
        $this->assertTrue($this->identity()->takenByAnother($newcomer, 'MAIN SAMPLE'));
        $this->assertFalse($this->identity()->takenByAnother($newcomer, 'Zezima'));
    }

    #[Test]
    public function separators_are_the_same_character_when_comparing_names(): void
    {
        User::factory()->create(['osrs_username' => 'Iron Man']);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->assertTrue($this->identity()->takenByAnother($newcomer, 'iron_man'));
        $this->assertTrue($this->identity()->takenByAnother($newcomer, 'Iron-Man'));
    }

    /** Your own name is not taken from you by yourself. */
    #[Test]
    public function an_account_does_not_hold_its_own_name_against_itself(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Main Sample']);

        $this->assertFalse($this->identity()->takenByAnother($user, 'Main Sample'));
    }

    /**
     * Proof is the line between a warning and a refusal. Until somebody has
     * played the account from a client holding their code, nobody can say
     * whose name it is; after that, they can.
     */
    #[Test]
    public function a_name_another_account_has_proved_cannot_be_claimed(): void
    {
        $this->fakeFound('Main Sample');
        User::factory()->create(['osrs_username' => 'Main Sample', 'osrs_proven_at' => now()]);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($newcomer)
            ->post('/welcome/osrs-username', ['osrs_username' => 'main sample'])
            ->assertSessionHasErrors('osrs_username');

        $this->assertNull($newcomer->fresh()->osrs_username);
    }

    /** Unproven is still only a warning — the name is stored. */
    #[Test]
    public function an_unproven_duplicate_is_not_refused(): void
    {
        $this->fakeFound('Main Sample');
        User::factory()->create(['osrs_username' => 'Main Sample', 'osrs_proven_at' => null]);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($newcomer)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Main Sample'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Main Sample', $newcomer->fresh()->osrs_username);
    }

    /** Re-saving your own proved name is not somebody else claiming it. */
    #[Test]
    public function the_account_that_proved_a_name_can_still_save_it(): void
    {
        $this->fakeFound('Main Sample');
        $owner = User::factory()->create(['osrs_username' => 'Main Sample', 'osrs_proven_at' => now()]);

        $this->actingAs($owner)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Main Sample'])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function settings_refuses_a_proved_name_too(): void
    {
        $this->fakeFound('Main Sample');
        User::factory()->create(['osrs_username' => 'Main Sample', 'osrs_proven_at' => now()]);
        $other = User::factory()->create(['osrs_username' => 'Zezima']);

        $this->actingAs($other)
            ->from('/settings/connections')
            ->put('/settings/connections/osrs', ['osrs_username' => 'Main Sample'])
            ->assertSessionHasErrors('osrs_username');

        $this->assertSame('Zezima', $other->fresh()->osrs_username);
    }

    #[Test]
    public function the_check_endpoint_answers_the_hiscores_and_the_duplicate_question(): void
    {
        $this->fakeFound('Main Sample');
        User::factory()->create(['osrs_username' => 'Main Sample']);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($newcomer)
            ->postJson('/welcome/osrs-username/check', ['osrs_username' => 'main sample'])
            ->assertOk()
            ->assertJson(['found' => true, 'displayName' => 'Main Sample', 'taken' => true, 'proven' => false]);

        // Nothing is stored by asking.
        $this->assertNull($newcomer->fresh()->osrs_username);
    }

    #[Test]
    public function saving_a_name_somebody_else_carries_warns_but_keeps_it(): void
    {
        $this->fakeFound('Main Sample');
        User::factory()->create(['osrs_username' => 'Main Sample']);
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($newcomer)
            ->post('/welcome/osrs-username', ['osrs_username' => 'Main Sample'])
            ->assertSessionHas('board-save-error', trans('auth.osrs_taken'));

        $this->assertSame('Main Sample', $newcomer->fresh()->osrs_username);
    }

    #[Test]
    public function a_found_name_is_stored_verified_and_in_wise_old_mans_casing(): void
    {
        $this->fakeFound('Main Sample');
        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $found = $this->identity()->apply($user, 'main sample');

        $this->assertTrue($found);
        $this->assertSame('Main Sample', $user->fresh()->osrs_username);
        $this->assertNotNull($user->fresh()->osrs_verified_at);
    }

    #[Test]
    public function a_missing_name_is_still_stored_but_left_unverified(): void
    {
        $this->fakeMissing();
        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $found = $this->identity()->apply($user, 'Zzqxwv Notrl');

        $this->assertFalse($found);
        $this->assertSame('Zzqxwv Notrl', $user->fresh()->osrs_username);
        $this->assertNull($user->fresh()->osrs_verified_at);
    }

    /**
     * An unreachable API must not be optimistic either. A wrong "verified" is
     * a player quietly missing from every leaderboard; an extra nudge to check
     * again costs nothing.
     */
    #[Test]
    public function an_unknown_answer_does_not_mark_the_account_verified(): void
    {
        Http::fake([self::PLAYER_URL => Http::response('', 503)]);
        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $this->assertNull($this->identity()->apply($user, 'Main Sample'));
        $this->assertSame('Main Sample', $user->fresh()->osrs_username);
        $this->assertNull($user->fresh()->osrs_verified_at);
    }

    /** The confirmation belongs to the name it was made about, not the account. */
    #[Test]
    public function renaming_to_an_unknown_account_clears_a_previous_verification(): void
    {
        // A sequence, not two fake() calls: Laravel keeps the FIRST matching
        // stub, so re-faking the same URL does not replace the earlier one.
        Http::fake([self::PLAYER_URL => Http::sequence()
            ->push(['displayName' => 'Main Sample'], 200)
            ->push(['code' => 'PLAYER_NOT_FOUND'], 404)]);

        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $this->identity()->apply($user, 'Main Sample');
        $this->assertNotNull($user->fresh()->osrs_verified_at);

        $this->identity()->apply($user, 'Someone Else');

        $this->assertSame('Someone Else', $user->fresh()->osrs_username);
        $this->assertNull($user->fresh()->osrs_verified_at);
    }

    #[Test]
    public function it_trims_surrounding_whitespace_before_storing(): void
    {
        $this->fakeMissing();
        $user = User::factory()->create(['osrs_username' => null]);

        $this->identity()->apply($user, '  Some Name  ');

        $this->assertSame('Some Name', $user->fresh()->osrs_username);
    }

    #[Test]
    public function rechecking_uses_the_name_already_on_the_account(): void
    {
        $this->fakeFound('Main Sample');
        $user = User::factory()->create(['osrs_username' => 'Main Sample', 'osrs_verified_at' => null]);

        $this->assertTrue($this->identity()->recheck($user));
        $this->assertNotNull($user->fresh()->osrs_verified_at);
    }

    #[Test]
    public function rechecking_an_account_with_no_name_spends_no_request(): void
    {
        Http::fake();
        $user = User::factory()->create(['osrs_username' => null]);

        $this->assertNull($this->identity()->recheck($user));
        Http::assertNothingSent();
    }
}
