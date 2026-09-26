<?php

namespace Tests\Feature;

use App\Events\Channels\BingoChannel;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reveal: squares start hidden and are drawn one at a time. Nobody but a host
 * may learn what a hidden square asks for, from any route.
 */
class BingoRevealTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private BingoCard $card;

    private User $host;

    private User $red;

    private User $blue;

    private Team $reds;

    private Team $blues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::create([
            'title' => 'Item Race',
            'type' => 'BINGO',
            'mode' => 'TEAM',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->card = $this->event->bingoCard()->create([
            'size' => 3,
            'win_condition' => 'LINE',
            'requires_approval' => false,
            'trust_runelite_completions' => true,
            'reveal' => true,
        ]);
        app(BingoService::class)->ensureSquares($this->card);

        foreach ($this->card->squares as $square) {
            $square->update(['title_override' => 'Secret '.($square->position + 1)]);
        }

        $this->host = User::factory()->create(['osrs_username' => 'Host Sample']);
        BoardAuthor::create(['event_id' => $this->event->id, 'user_id' => $this->host->id, 'is_owner' => true]);

        [$this->red, $this->reds] = $this->member('Reds', 'Red Sample');
        [$this->blue, $this->blues] = $this->member('Blues', 'Blue Sample');
    }

    /** @return array{0: User, 1: Team} */
    private function member(string $team, string $rsn): array
    {
        $user = User::factory()->create(['osrs_username' => $rsn]);
        $model = Team::create(['name' => $team]);
        TeamMember::create(['team_id' => $model->id, 'user_id' => $user->id]);
        $this->event->eventTeams()->create(['team_id' => $model->id]);

        return [$user, $model];
    }

    private function square(int $position = 0): BingoSquare
    {
        return $this->card->squares()->where('position', $position)->first();
    }

    private function reveal(?User $as = null)
    {
        return $this->actingAs($as ?? $this->host)->post("/events/{$this->event->id}/bingo/reveal");
    }

    private function claim(User $user, int $position = 0)
    {
        return $this->actingAs($user)->post("/events/{$this->event->id}/bingo/squares/{$this->square($position)->id}/claim");
    }

    /** The one square that has been drawn so far. */
    private function drawn(): BingoSquare
    {
        return $this->card->squares()->whereNotNull('revealed_at')->sole();
    }

    // ------------------------------------------------------------ the page

    #[Test]
    public function a_player_sees_every_square_as_a_blank_until_it_is_drawn(): void
    {
        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('card.squares.0.hidden', true)
                ->where('card.squares.0.label', null)
                ->where('card.squares.0.titleOverride', null)
                ->where('card.squares.0.task', null)
                ->where('card.revealState.revealed', 0)
                ->where('card.revealState.remaining', 9));

        $this->assertStringNotContainsString('Secret', $this->actingAs($this->red)->get("/events/{$this->event->id}")->getContent());
    }

    #[Test]
    public function a_host_sees_what_is_hidden_and_that_it_is(): void
    {
        $this->actingAs($this->host)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('card.squares.0.hidden', true)
                ->where('card.squares.0.label', 'Secret 1'));
    }

    #[Test]
    public function a_drawn_square_is_shown_to_everyone(): void
    {
        $this->reveal()->assertSessionHas('board-save');

        $position = $this->drawn()->position;

        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where("card.squares.{$position}.hidden", false)
                ->where("card.squares.{$position}.label", 'Secret '.($position + 1))
                ->where('card.revealState.revealed', 1)
                ->where('card.revealState.remaining', 8));
    }

    #[Test]
    public function only_a_host_may_draw(): void
    {
        $this->reveal($this->red)->assertForbidden();
        $this->assertSame(0, $this->card->squares()->whereNotNull('revealed_at')->count());
    }

    #[Test]
    public function the_square_detail_of_a_hidden_square_is_for_hosts_only(): void
    {
        $partial = [
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => 'Events/Bingo',
            'X-Inertia-Partial-Data' => 'squareDetail',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        ];

        $this->actingAs($this->red)->get("/events/{$this->event->id}?square=0", $partial)
            ->assertJsonPath('props.squareDetail', null);

        $this->actingAs($this->host)->get("/events/{$this->event->id}?square=0", $partial)
            ->assertJsonPath('props.squareDetail.position', 0);
    }

    // ------------------------------------------------------------ claiming

    #[Test]
    public function a_hidden_square_cannot_be_claimed_and_a_drawn_one_can(): void
    {
        $this->claim($this->red, 0)->assertSessionHas('board-save-error', "This square hasn't been revealed yet.");
        $this->assertSame(0, BingoCompletion::count());

        $this->reveal();
        $this->claim($this->red, $this->drawn()->position)->assertSessionHas('board-save');
        $this->assertSame(1, BingoCompletion::count());
    }

    #[Test]
    public function lockout_still_holds_on_a_drawn_square(): void
    {
        $this->card->update(['lockout' => true]);
        $this->reveal();
        $position = $this->drawn()->position;

        $this->claim($this->red, $position)->assertSessionHas('board-save');
        $this->claim($this->blue, $position)->assertSessionHas('board-save-error', 'Reds got this square first.');
    }

    // --------------------------------------------------------------- draws

    #[Test]
    public function drawing_stops_at_the_limit(): void
    {
        $this->card->update(['reveal_limit' => 2]);

        $this->reveal()->assertSessionHas('board-save');
        $this->reveal()->assertSessionHas('board-save');
        $this->reveal()->assertSessionHas('board-save-error', 'There are no more squares to reveal.');

        $this->assertSame(2, $this->card->squares()->whereNotNull('revealed_at')->count());
        $this->assertSame(0, app(BingoService::class)->revealState($this->event, $this->card->fresh())['remaining']);
    }

    #[Test]
    public function free_and_empty_squares_are_never_drawn(): void
    {
        $this->square(0)->update(['is_wildcard' => true]);
        $this->card->squares()->where('position', '>', 1)->update(['title_override' => null]);

        $this->reveal()->assertSessionHas('board-save');
        $this->reveal()->assertSessionHas('board-save-error', 'There are no more squares to reveal.');

        $this->assertSame(1, $this->drawn()->position);

        // The free square was never hidden in the first place.
        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->where('card.squares.0.hidden', false)->where('card.squares.0.isWildcard', true));
    }

    #[Test]
    public function nothing_is_drawn_once_the_event_has_ended(): void
    {
        $this->event->forceFill(['closed_at' => now()])->save();

        $this->reveal()->assertSessionHas('board-save-error');
        $this->assertSame(0, $this->card->squares()->whereNotNull('revealed_at')->count());
    }

    #[Test]
    public function a_card_without_reveal_hides_nothing_and_cannot_draw(): void
    {
        $this->card->update(['reveal' => false]);

        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('card.squares.0.hidden', false)
                ->where('card.squares.0.label', 'Secret 1')
                ->where('card.revealState', null));

        $this->reveal()->assertSessionHas('board-save-error', "This card doesn't reveal squares one at a time.");
    }

    // --------------------------------------------------------------- timer

    #[Test]
    public function the_timer_draws_at_the_start_and_then_every_interval(): void
    {
        $this->event->update(['start_date' => now()->toDateString()]);
        $this->card->update(['reveal_every_minutes' => 30, 'reveal_limit' => 2]);

        $this->artisan('bingo:reveal-due')->assertSuccessful();
        $this->assertSame(1, $this->card->squares()->whereNotNull('revealed_at')->count());

        $this->travel(10)->minutes();
        $this->artisan('bingo:reveal-due');
        $this->assertSame(1, $this->card->squares()->whereNotNull('revealed_at')->count());

        $this->travel(25)->minutes();
        $this->artisan('bingo:reveal-due');
        $this->assertSame(2, $this->card->squares()->whereNotNull('revealed_at')->count());

        // The limit.
        $this->travel(2)->hours();
        $this->artisan('bingo:reveal-due');
        $this->assertSame(2, $this->card->squares()->whereNotNull('revealed_at')->count());
    }

    #[Test]
    public function the_timer_waits_for_the_start_and_holds_while_paused(): void
    {
        $this->card->update(['reveal_every_minutes' => 5]);

        $this->event->update(['start_date' => now()->addDays(2)->toDateString()]);
        $this->artisan('bingo:reveal-due');
        $this->assertSame(0, $this->card->squares()->whereNotNull('revealed_at')->count());

        $this->event->forceFill(['start_date' => now()->toDateString(), 'paused_at' => now()])->save();
        $this->artisan('bingo:reveal-due');
        $this->assertSame(0, $this->card->squares()->whereNotNull('revealed_at')->count());
    }

    #[Test]
    public function a_card_without_a_timer_is_left_to_the_host(): void
    {
        $this->artisan('bingo:reveal-due');
        $this->assertSame(0, $this->card->squares()->whereNotNull('revealed_at')->count());
    }

    // --------------------------------------------------------------- live

    #[Test]
    public function the_live_payload_never_carries_a_hidden_square(): void
    {
        $this->reveal();
        $drawn = $this->drawn()->position;

        $payload = app(BingoChannel::class)->payload($this->event->fresh());

        $this->assertStringNotContainsString('Secret '.(($drawn + 1) % 9 + 1), json_encode($payload));
        $this->assertSame('Secret '.($drawn + 1), $payload['squares'][$drawn]['label']);
        $this->assertSame(1, collect($payload['squares'])->where('hidden', false)->count());
        $this->assertSame(1, $payload['revealState']['revealed']);
    }

    #[Test]
    public function the_live_fingerprint_moves_on_a_draw(): void
    {
        $channel = app(BingoChannel::class);
        $before = $channel->fingerprint($this->event->fresh());

        $this->reveal();

        $this->assertNotSame($before, $channel->fingerprint($this->event->fresh()));
    }

    // -------------------------------------------------------------- plugin

    #[Test]
    public function the_plugin_neither_targets_nor_claims_a_hidden_square(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $task = Task::create(['title' => 'Abyssal whip', 'wiki_page_id' => 4151]);
        $this->card->squares()->update(['task_id' => $task->id, 'title_override' => null]);
        $this->card->squares()->where('position', '>', 0)->update(['task_id' => null, 'title_override' => 'Secret']);
        EventParticipant::firstOrCreate(['event_id' => $this->event->id, 'user_id' => $this->red->id]);

        $plugin = $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($this->red));

        $plugin->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('events.0.targets', []);
        $this->report($plugin)->assertCreated()->assertJsonPath('claims', []);
        $this->assertSame(0, BingoCompletion::count());

        $this->square(0)->update(['revealed_at' => now()]);

        $plugin->getJson('/api/plugin/v1/events')->assertJsonPath('events.0.targets.0.name', 'Abyssal whip');
        $this->report($plugin)->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');
    }

    private function report($plugin)
    {
        return $plugin->postJson('/api/plugin/v1/completions', [
            'client_event_id' => (string) str()->uuid(),
            'kind' => 'item',
            'name' => 'Abyssal whip',
            'quantity' => 1,
            'rsn' => 'Red Sample',
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    // ------------------------------------------------------------ settings

    #[Test]
    public function reveal_can_only_be_switched_on_before_the_first_claim(): void
    {
        $this->card->update(['reveal' => false]);
        $this->square(3)->update(['revealed_at' => now()]);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['reveal' => true])->assertSessionHas('board-save');
        $this->assertTrue($this->card->fresh()->reveal);
        // Switched on, every square starts hidden again.
        $this->assertNull($this->square(3)->revealed_at);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['reveal' => false])->assertSessionHas('board-save');
        $this->claim($this->red, 0);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['reveal' => true])
            ->assertSessionHas('board-save-error', "Reveal can't be switched on once squares have been claimed.");
        $this->assertFalse($this->card->fresh()->reveal);
    }

    #[Test]
    public function creating_a_bingo_stores_reveal_its_limit_and_timer(): void
    {
        $creator = User::factory()->create();
        $creator->grantStarterAccess();

        $this->actingAs($creator)->post('/events', [
            'title' => 'Reveal race',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'reveal' => true,
            'reveal_limit' => 12,
            'reveal_every_minutes' => 60,
        ])->assertSessionHasNoErrors();

        $card = Event::where('title', 'Reveal race')->first()->bingoCard;

        $this->assertTrue($card->reveal);
        $this->assertSame(12, $card->reveal_limit);
        $this->assertSame(60, $card->reveal_every_minutes);
    }

    #[Test]
    public function the_settings_modal_can_clear_the_limit(): void
    {
        $this->card->update(['reveal_limit' => 12]);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}", [
            'reveal' => true,
            'reveal_limit' => null,
            'reveal_every_minutes' => 15,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->card->fresh()->reveal_limit);
        $this->assertSame(15, $this->card->fresh()->reveal_every_minutes);

        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('event.card.reveal', true)
                ->where('event.card.revealLimit', null)
                ->where('event.card.revealEveryMinutes', 15));
    }
}
