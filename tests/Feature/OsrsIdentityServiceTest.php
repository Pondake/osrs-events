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

    private function fakeFound(string $displayName = 'Pondake'): void
    {
        Http::fake([self::PLAYER_URL => Http::response(['displayName' => $displayName])]);
    }

    private function fakeMissing(): void
    {
        Http::fake([self::PLAYER_URL => Http::response(['code' => 'PLAYER_NOT_FOUND'], 404)]);
    }

    #[Test]
    public function a_found_name_is_stored_verified_and_in_wise_old_mans_casing(): void
    {
        $this->fakeFound('Pondake');
        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $found = $this->identity()->apply($user, 'pondake');

        $this->assertTrue($found);
        $this->assertSame('Pondake', $user->fresh()->osrs_username);
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

        $this->assertNull($this->identity()->apply($user, 'Pondake'));
        $this->assertSame('Pondake', $user->fresh()->osrs_username);
        $this->assertNull($user->fresh()->osrs_verified_at);
    }

    /** The confirmation belongs to the name it was made about, not the account. */
    #[Test]
    public function renaming_to_an_unknown_account_clears_a_previous_verification(): void
    {
        // A sequence, not two fake() calls: Laravel keeps the FIRST matching
        // stub, so re-faking the same URL does not replace the earlier one.
        Http::fake([self::PLAYER_URL => Http::sequence()
            ->push(['displayName' => 'Pondake'], 200)
            ->push(['code' => 'PLAYER_NOT_FOUND'], 404)]);

        $user = User::factory()->create(['osrs_username' => null, 'osrs_verified_at' => null]);

        $this->identity()->apply($user, 'Pondake');
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
        $this->fakeFound('Pondake');
        $user = User::factory()->create(['osrs_username' => 'Pondake', 'osrs_verified_at' => null]);

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
