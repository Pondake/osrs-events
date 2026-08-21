<?php

namespace Tests\Feature;

use App\Services\WiseOldManService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Wise Old Man client, with their API faked.
 *
 * Every response shape below was taken from the real API before being frozen
 * here — the 404 body in particular ({"code":"PLAYER_NOT_FOUND"}), because
 * "never tracked returns an empty response" was the assumption these tests
 * exist to stop anyone acting on.
 */
class WiseOldManServiceTest extends TestCase
{
    private const PLAYER_URL = 'api.wiseoldman.net/v2/players/*';

    private function service(): WiseOldManService
    {
        return app(WiseOldManService::class);
    }

    #[Test]
    public function it_reports_a_known_player_as_found_with_their_canonical_name(): void
    {
        Http::fake([self::PLAYER_URL => Http::response([
            'id' => 592201,
            'username' => 'pondake',
            'displayName' => 'Pondake',
            'status' => 'active',
        ])]);

        $this->assertSame(
            ['found' => true, 'displayName' => 'Pondake'],
            $this->service()->findPlayer('pondake'),
        );
    }

    #[Test]
    public function it_reports_an_untracked_player_as_not_found(): void
    {
        Http::fake([self::PLAYER_URL => Http::response(
            ['code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found'],
            404,
        )]);

        $this->assertSame(
            ['found' => false, 'displayName' => null],
            $this->service()->findPlayer('Zzqxwv Notreal'),
        );
    }

    /**
     * The distinction the whole warning flow rests on: a server error is not
     * evidence that an account is missing. Reporting it as "not found" tells
     * someone their own RSN is a typo because a third party had a bad minute.
     */
    #[Test]
    public function a_server_error_is_unknown_rather_than_not_found(): void
    {
        Http::fake([self::PLAYER_URL => Http::response('nope', 500)]);

        $this->assertSame(
            ['found' => null, 'displayName' => null],
            $this->service()->findPlayer('Pondake'),
        );
    }

    #[Test]
    public function an_unreachable_api_is_unknown_rather_than_not_found(): void
    {
        Http::fake([self::PLAYER_URL => fn () => throw new ConnectionException('timed out')]);

        $this->assertSame(
            ['found' => null, 'displayName' => null],
            $this->service()->findPlayer('Pondake'),
        );
    }

    /** A rate-limit rejection is the most likely real 429, and also not a 404. */
    #[Test]
    public function a_rate_limit_rejection_is_unknown_rather_than_not_found(): void
    {
        Http::fake([self::PLAYER_URL => Http::response(['message' => 'Too many requests'], 429)]);

        $this->assertNull($this->service()->findPlayer('Pondake')['found']);
    }

    #[Test]
    public function it_reads_gains_in_wise_old_mans_own_delta_shape(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response([
            'startsAt' => '2026-08-01T00:00:00.000Z',
            'endsAt' => '2026-08-20T00:00:00.000Z',
            'data' => ['skills' => ['mining' => ['metric' => 'mining', 'experience' => [
                'gained' => 2360640,
                'start' => 32899804,
                'end' => 35260444,
            ]]]],
        ])]);

        $this->assertSame(
            ['gained' => 2360640, 'start' => 32899804, 'end' => 35260444],
            $this->service()->gained('Pondake', 'mining', 'skill', Carbon::parse('2026-08-01'), Carbon::parse('2026-08-20')),
        );
    }

    /**
     * Rollbacks and hiscores glitches can hand back a negative gain. A
     * leaderboard that can go below zero is a bug report waiting to happen.
     */
    #[Test]
    public function it_clamps_a_negative_gain_to_zero(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response([
            'data' => ['skills' => ['mining' => ['experience' => [
                'gained' => -5000,
                'start' => 100,
                'end' => 50,
            ]]]],
        ])]);

        $delta = $this->service()->gained('Pondake', 'mining', 'skill', Carbon::parse('2026-08-01'), Carbon::parse('2026-08-20'));

        $this->assertSame(0, $delta['gained']);
    }

    #[Test]
    public function gains_for_an_untracked_player_are_null_not_zero(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response(
            ['code' => 'PLAYER_NOT_FOUND'],
            404,
        )]);

        $this->assertNull(
            $this->service()->gained('Zzqxwv Notreal', 'mining', 'skill', Carbon::parse('2026-08-01'), Carbon::parse('2026-08-20')),
        );
    }

    /** A window with no snapshots in it returns the envelope but no metric. */
    #[Test]
    public function gains_are_null_when_the_metric_is_absent_from_the_response(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response([
            'data' => ['skills' => []],
        ])]);

        $this->assertNull(
            $this->service()->gained('Pondake', 'mining', 'skill', Carbon::parse('2026-08-01'), Carbon::parse('2026-08-20')),
        );
    }

    #[Test]
    public function it_identifies_itself_and_sends_the_window_as_iso_utc(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['data' => ['skills' => []]])]);
        config(['services.wom.user_agent' => 'osrs-events (contact: someone@example.com)']);

        $this->service()->gained('Pondake', 'mining', 'skill', Carbon::parse('2026-08-01 12:00:00'), Carbon::parse('2026-08-20 12:00:00'));

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent', 'osrs-events (contact: someone@example.com)')
                && str_contains($request->url(), 'startDate=2026-08-01T12%3A00%3A00.000Z')
                && str_contains($request->url(), 'endDate=2026-08-20T12%3A00%3A00.000Z');
        });
    }
}
