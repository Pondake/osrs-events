<?php

namespace Tests\Feature;

use App\Console\Commands\SweepPushNotifications;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\OsrsAccount;
use App\Models\Setting;
use App\Models\User;
use App\Services\RaceAnnouncer;
use App\Services\RaceRankNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An account with alts has a standing per character, and everything that
 * talks about a race outside its page must still count it once — on its best
 * character, never the sum. The page does (EventStandingsService::forEvent);
 * these are the three copies of that ranking elsewhere.
 */
class RaceAltsTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $withAlt;

    private User $rival;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('discord_webhooks_enabled', true);
        Http::fake(['discord.com/*' => Http::response('', 204)]);

        $this->event = Event::create([
            'title' => 'Mining race',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeek(),
        ]);
        $this->event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        // Main 100, alt 500: the account's line is 500. The rival's 300 sits
        // between the two, which is what a per-row count gets wrong.
        $this->withAlt = User::factory()->create(['osrs_username' => 'Main Sample']);
        $alt = OsrsAccount::create(['user_id' => $this->withAlt->id, 'username' => 'Iron Sample', 'position' => 1]);
        $this->rival = User::factory()->create(['osrs_username' => 'Rival Sample']);

        $this->standing($this->withAlt, 'Main Sample', 100, $this->withAlt->osrsAccounts()->first()->id);
        $this->standing($this->withAlt, 'Iron Sample', 500, $alt->id);
        $this->standing($this->rival, 'Rival Sample', 300, $this->rival->osrsAccounts()->first()->id);
    }

    private function standing(User $user, string $name, int $gained, string $account): void
    {
        EventStanding::create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'osrs_account_id' => $account,
            'username' => $name,
            'gained' => $gained,
            'synced_at' => now(),
        ]);
    }

    #[Test]
    public function the_rank_notifier_ranks_each_account_once_on_its_best_character(): void
    {
        $ranks = app(RaceRankNotifier::class)->snapshot($this->event);

        $this->assertSame([$this->withAlt->id => 1, $this->rival->id => 2], $ranks);
    }

    #[Test]
    public function the_discord_podium_names_an_account_once(): void
    {
        $this->assertTrue(app(RaceAnnouncer::class)->final($this->event->fresh()));

        Http::assertSent(function ($request) {
            $body = $request->data()['content'];

            return str_contains($body, '1. Iron Sample')
                && str_contains($body, '2. Rival Sample')
                && ! str_contains($body, 'Main Sample');
        });
    }

    #[Test]
    public function the_closing_push_places_each_account_once(): void
    {
        $placings = (new \ReflectionMethod(SweepPushNotifications::class, 'placings'))
            ->invoke(app(SweepPushNotifications::class), $this->event);

        $this->assertSame([$this->withAlt->id => 1, $this->rival->id => 2], $placings);
    }
}
