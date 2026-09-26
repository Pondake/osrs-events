<?php

namespace Tests\Feature;

use App\Events\Channels\BingoChannel;
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
 * Lockout: on a team card the first team to have a square approved keeps it.
 */
class BingoLockoutTest extends TestCase
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
            'requires_approval' => true,
            'trust_runelite_completions' => true,
            'lockout' => true,
        ]);
        app(BingoService::class)->ensureSquares($this->card);

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

    private function claim(User $user, int $position = 0, array $data = ['proof_url' => 'https://i.imgur.com/x.png'])
    {
        return $this->actingAs($user)->post("/events/{$this->event->id}/bingo/squares/{$this->square($position)->id}/claim", $data);
    }

    private function review(BingoCompletion $claim, string $status)
    {
        return $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo/claims/{$claim->id}", ['status' => $status]);
    }

    private function claimOf(Team $team, int $position = 0): ?BingoCompletion
    {
        return BingoCompletion::where('bingo_square_id', $this->square($position)->id)->where('team_id', $team->id)->first();
    }

    private function instant(): void
    {
        $this->card->update(['requires_approval' => false]);
    }

    // ------------------------------------------------------------- claiming

    #[Test]
    public function the_first_team_keeps_a_square_and_the_other_is_refused(): void
    {
        $this->instant();

        $this->claim($this->red, data: [])->assertSessionHas('board-save');
        $this->claim($this->blue, data: [])->assertSessionHas('board-save-error', 'Reds got this square first.');

        $this->assertSame('APPROVED', $this->claimOf($this->reds)->status);
        $this->assertNull($this->claimOf($this->blues));
    }

    #[Test]
    public function a_square_nobody_holds_takes_claims_from_every_team(): void
    {
        $this->claim($this->red);
        $this->claim($this->blue);

        $this->assertSame('PENDING', $this->claimOf($this->reds)->status);
        $this->assertSame('PENDING', $this->claimOf($this->blues)->status);
    }

    #[Test]
    public function without_lockout_both_teams_score_the_same_square(): void
    {
        $this->card->update(['lockout' => false]);
        $this->instant();

        $this->claim($this->red, data: []);
        $this->claim($this->blue, data: []);

        $this->assertSame('APPROVED', $this->claimOf($this->reds)->status);
        $this->assertSame('APPROVED', $this->claimOf($this->blues)->status);
    }

    #[Test]
    public function lockout_does_nothing_on_a_solo_event(): void
    {
        $this->event->update(['mode' => 'SOLO']);

        $this->assertFalse($this->card->fresh()->usesLockout());
    }

    // ---------------------------------------------------------------- review

    #[Test]
    public function a_host_can_only_approve_the_claim_that_came_first(): void
    {
        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->claim($this->blue);

        $this->review($this->claimOf($this->blues), 'APPROVED')
            ->assertSessionHas('board-save-error', 'Reds claimed this square earlier. Rule on their claim first.');
        $this->assertSame('PENDING', $this->claimOf($this->blues)->status);

        $this->review($this->claimOf($this->reds), 'APPROVED')->assertSessionHas('board-save');
        $this->assertSame('APPROVED', $this->claimOf($this->reds)->status);
    }

    #[Test]
    public function a_rejected_claim_frees_the_square_for_the_next_in_line(): void
    {
        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->claim($this->blue);

        $this->review($this->claimOf($this->reds), 'REJECTED');
        $this->review($this->claimOf($this->blues), 'APPROVED')->assertSessionHas('board-save');

        $this->assertSame('APPROVED', $this->claimOf($this->blues)->status);
    }

    #[Test]
    public function once_a_square_is_taken_the_other_claims_leave_the_queue_and_new_ones_are_refused(): void
    {
        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->claim($this->blue);

        $this->review($this->claimOf($this->reds), 'APPROVED');

        $queue = app(BingoService::class)->pendingQueue($this->card->fresh());
        $this->assertCount(0, $queue);
        // Kept, not rejected: it is next in line if the Reds' claim is overturned.
        $this->assertSame('PENDING', $this->claimOf($this->blues)->status);

        $this->review($this->claimOf($this->blues), 'APPROVED')
            ->assertSessionHas('board-save-error', 'Reds got this square first.');
    }

    #[Test]
    public function the_queue_says_who_is_ahead(): void
    {
        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->claim($this->blue);

        $queue = app(BingoService::class)->pendingQueue($this->card->fresh())->keyBy('competitor');

        $this->assertNull($queue['Reds']['lockoutAhead']);
        $this->assertSame('Reds', $queue['Blues']['lockoutAhead']);
    }

    #[Test]
    public function overturning_the_holder_hands_the_square_on(): void
    {
        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->claim($this->blue);
        $this->review($this->claimOf($this->reds), 'APPROVED');

        $this->review($this->claimOf($this->reds), 'REJECTED');

        // A manual claim on a reviewed card still needs a host, now first in line.
        $this->assertSame('PENDING', $this->claimOf($this->blues)->status);
        $this->review($this->claimOf($this->blues), 'APPROVED')->assertSessionHas('board-save');
    }

    #[Test]
    public function withdrawing_an_instant_claim_gives_the_square_to_the_next_team(): void
    {
        $this->instant();
        $this->claim($this->red, data: []);

        // Blues waiting behind: a claim made while the square was still
        // contested, the way an unproven name leaves one pending.
        BingoCompletion::create([
            'bingo_square_id' => $this->square()->id,
            'team_id' => $this->blues->id,
            'marked_by' => $this->blue->id,
            'completed_via' => 'MANUAL',
            'status' => 'PENDING',
        ]);

        $this->claim($this->red, data: [])->assertSessionHas('board-save');

        $this->assertNull($this->claimOf($this->reds));
        $this->assertSame('APPROVED', $this->claimOf($this->blues)->status);
    }

    // ------------------------------------------------------------ ordering

    #[Test]
    public function an_instant_claim_waits_behind_an_earlier_one(): void
    {
        $bingo = app(BingoService::class);

        $bingo->createClaim($this->card, $this->square(), [
            'bingo_square_id' => $this->square()->id,
            'team_id' => $this->reds->id,
            'marked_by' => $this->red->id,
            'status' => 'PENDING',
            'claimed_at' => now()->subMinute(),
        ]);

        $blue = $bingo->createClaim($this->card, $this->square(), [
            'bingo_square_id' => $this->square()->id,
            'team_id' => $this->blues->id,
            'marked_by' => $this->blue->id,
            'status' => 'APPROVED',
        ]);

        $this->assertSame('PENDING', $blue->status);
    }

    // --------------------------------------------------------------- plugin

    private function pluginTask(): Task
    {
        $task = Task::create(['title' => 'Abyssal whip', 'wiki_page_id' => 4151]);
        $this->square()->update(['task_id' => $task->id]);

        return $task;
    }

    private function report(User $user, $occurredAt)
    {
        EventParticipant::firstOrCreate(['event_id' => $this->event->id, 'user_id' => $user->id]);

        return $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($user))
            ->postJson('/api/plugin/v1/completions', [
                'client_event_id' => (string) str()->uuid(),
                'kind' => 'item',
                'name' => 'Abyssal whip',
                'quantity' => 1,
                'rsn' => $user->osrs_username,
                'occurred_at' => $occurredAt->toIso8601String(),
            ]);
    }

    #[Test]
    public function the_plugin_timestamp_decides_who_was_first(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $this->pluginTask();

        // Reds claim by hand now; the Blues' client reports a drop from five
        // minutes ago that it had not sent yet.
        $this->claim($this->red);
        $this->report($this->blue, now()->subMinutes(5))->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame('APPROVED', $this->claimOf($this->blues)->status);
        $this->assertSame('PENDING', $this->claimOf($this->reds)->status);
    }

    #[Test]
    public function the_plugin_does_not_target_or_claim_a_square_another_team_holds(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $this->pluginTask();
        $this->instant();
        $this->claim($this->red, data: []);

        EventParticipant::firstOrCreate(['event_id' => $this->event->id, 'user_id' => $this->blue->id]);
        $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($this->blue))
            ->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonPath('events.0.targets', []);

        $this->report($this->blue, now())->assertCreated()->assertJsonPath('claims', []);
        $this->assertNull($this->claimOf($this->blues));
    }

    #[Test]
    public function a_plugin_claim_behind_an_earlier_manual_one_waits(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $this->pluginTask();

        $this->claim($this->red);
        $this->travel(1)->minutes();
        $this->report($this->blue, now())->assertCreated()->assertJsonPath('claims.0.status', 'PENDING');
    }

    // ----------------------------------------------------------------- win

    #[Test]
    public function a_line_does_not_win_a_lockout_card(): void
    {
        $this->instant();

        foreach ([0, 1, 2] as $position) {
            $this->claim($this->red, $position, []);
        }

        $standings = app(BingoService::class)->standings($this->event, $this->card->fresh());

        $this->assertFalse($standings->first()['won']);
        $this->assertSame(0, $this->event->finishes()->count());
    }

    #[Test]
    public function the_team_with_the_most_points_wins_when_the_card_closes(): void
    {
        $this->instant();
        $this->claim($this->red, 0, []);
        $this->claim($this->red, 1, []);
        $this->claim($this->blue, 4, []);

        $this->event->update(['end_date' => now()->subDays(2)->toDateString()]);

        $standings = app(BingoService::class)->standings($this->event->fresh(), $this->card->fresh())->keyBy('name');

        $this->assertTrue($standings['Reds']['won']);
        $this->assertFalse($standings['Blues']['won']);
    }

    // ------------------------------------------------------------ settings

    #[Test]
    public function lockout_can_only_be_switched_before_the_first_claim(): void
    {
        $this->card->update(['lockout' => false]);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['lockout' => true])->assertSessionHas('board-save');
        $this->assertTrue($this->card->fresh()->lockout);

        $this->claim($this->red);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['lockout' => false])
            ->assertSessionHas('board-save-error', "Lockout can't be switched once squares have been claimed.");
        $this->assertTrue($this->card->fresh()->lockout);
    }

    #[Test]
    public function lockout_is_refused_on_a_solo_event(): void
    {
        $this->event->update(['mode' => 'SOLO']);
        $this->card->update(['lockout' => false]);

        $this->actingAs($this->host)->patch("/events/{$this->event->id}/bingo", ['lockout' => true])
            ->assertSessionHas('board-save-error', 'Lockout is only available on a team event.');
    }

    #[Test]
    public function creating_a_team_bingo_stores_lockout_and_a_solo_one_does_not(): void
    {
        $creator = User::factory()->create();
        $creator->grantStarterAccess();

        foreach (['TEAM' => true, 'SOLO' => false] as $mode => $expected) {
            $this->actingAs($creator)->post('/events', [
                'title' => "Lockout {$mode}",
                'type' => 'BINGO',
                'mode' => $mode,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addWeek()->toDateString(),
                'lockout' => true,
            ])->assertSessionHasNoErrors();

            $this->assertSame($expected, Event::where('title', "Lockout {$mode}")->first()->bingoCard->lockout);
        }
    }

    // ---------------------------------------------------------------- live

    #[Test]
    public function the_live_fingerprint_moves_when_a_square_is_taken(): void
    {
        $channel = app(BingoChannel::class);

        $this->claim($this->red);
        $before = $channel->fingerprint($this->event->fresh());

        $this->review($this->claimOf($this->reds), 'APPROVED');

        $this->assertNotSame($before, $channel->fingerprint($this->event->fresh()));
    }

    #[Test]
    public function the_page_and_the_settings_say_lockout_is_on(): void
    {
        $this->actingAs($this->red)->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('card.lockout', true)
                ->where('event.card.lockout', true));
    }
}
