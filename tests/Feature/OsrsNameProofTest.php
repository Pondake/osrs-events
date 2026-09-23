<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Board;
use App\Models\Event;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\User;
use App\Services\BingoService;
use App\Services\OsrsIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Proving the OSRS name on an account, and what that proof buys.
 *
 * The problem it answers: anybody can type a name that belongs to somebody
 * else, and two accounts on the dev database really did both hold "Pondake".
 * Enforcing uniqueness on its own would be worse than the problem — it would
 * let a stranger claim your name before you arrived — so nothing is taken
 * away from anybody. An unproven name loses the shortcut instead: the claim
 * goes to a host, who approves it like any other.
 *
 * What a proof is worth is written out in the osrs_proven_at migration. Short
 * version: it beats a collision and a casual squatter, and it does not beat
 * somebody calling the API by hand with their own code.
 */
class OsrsNameProofTest extends TestCase
{
    use RefreshDatabase;

    private User $player;

    private string $code;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('runelite_plugin_mode', 'testing');
        $this->player = User::factory()->create(['osrs_username' => 'Iron Pondake']);
        $this->code = PluginToken::issueFor($this->player);
    }

    private function api(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->code}");
    }

    // ------------------------------------------------------- proving it

    #[Test]
    public function the_character_the_client_is_logged_in_as_proves_the_name(): void
    {
        $this->api()
            ->postJson('/api/plugin/v1/identity', ['rsn' => 'iron_pondake'])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('proven', true);

        $this->assertNotNull($this->player->fresh()->osrs_proven_at);
        $this->assertSame('runelite', $this->player->fresh()->osrs_proven_via);
    }

    #[Test]
    public function a_different_character_proves_nothing_when_the_plugin_adds_no_alts(): void
    {
        $this->api()
            ->postJson('/api/plugin/v1/identity', ['rsn' => 'Someone Else', 'add_alt' => false])
            ->assertOk()
            ->assertJsonPath('matched', false)
            ->assertJsonPath('added', false)
            ->assertJsonPath('reason', 'disabled')
            ->assertJsonPath('proven', false);

        $this->assertNull($this->player->fresh()->osrs_proven_at);
        $this->assertSame(1, $this->player->osrsAccounts()->count());
    }

    /**
     * Plenty of people have a second character. Logging into it is not a
     * reason to un-prove the first, so a mismatch leaves an earlier proof
     * standing rather than clearing it.
     */
    #[Test]
    public function a_different_character_does_not_undo_an_earlier_proof(): void
    {
        $this->api()->postJson('/api/plugin/v1/identity', ['rsn' => 'Iron Pondake'])->assertOk();
        $proven = $this->player->fresh()->osrs_proven_at;

        $this->api()->postJson('/api/plugin/v1/identity', ['rsn' => 'Alt Account'])->assertJsonPath('added', true);

        $this->assertEquals($proven, $this->player->fresh()->osrs_proven_at);
        $this->assertSame('Iron Pondake', $this->player->fresh()->osrs_username);
    }

    #[Test]
    public function the_events_call_says_whether_the_name_is_proven(): void
    {
        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('proven', false);

        $this->api()->postJson('/api/plugin/v1/identity', ['rsn' => 'Iron Pondake'])->assertOk();

        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('proven', true);
    }

    // ------------------------------------------------------- losing it

    #[Test]
    public function renaming_the_account_starts_the_proof_over(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*' => Http::response(['displayName' => 'Other Name'])]);
        $this->player->forceFill(['osrs_proven_at' => now(), 'osrs_proven_via' => 'runelite'])->save();

        app(OsrsIdentityService::class)->apply($this->player, 'Other Name');

        $this->assertNull($this->player->fresh()->osrs_proven_at);
        $this->assertNull($this->player->fresh()->osrs_proven_via);
    }

    /** The recheck button re-saves the same name. That is not a rename. */
    #[Test]
    public function rechecking_the_same_name_keeps_the_proof(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*' => Http::response(['displayName' => 'Iron Pondake'])]);
        $this->player->forceFill(['osrs_proven_at' => now(), 'osrs_proven_via' => 'runelite'])->save();

        app(OsrsIdentityService::class)->recheck($this->player);

        $this->assertNotNull($this->player->fresh()->osrs_proven_at);
    }

    // -------------------------------------------- what the proof buys

    /** @return array{0: Event, 1: BingoSquare} */
    private function claimableCard(): array
    {
        $event = (new Event)->forceFill([
            'title' => 'Clan night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->save();

        // requires_approval false: the host said claims need no review at
        // all, which is exactly the setting the proof check has to override.
        $card = $event->bingoCard()->create(['size' => 3, 'requires_approval' => false]);
        app(BingoService::class)->ensureSquares($card);

        return [$event, $card->squares()->orderBy('position')->first()];
    }

    private function claim(Event $event, $square): void
    {
        $this->actingAs($this->player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", []);
    }

    #[Test]
    public function an_unproven_name_sends_a_claim_to_a_host_even_on_a_board_that_reviews_nothing(): void
    {
        Setting::set('runelite_plugin_mode', 'live');
        [$event, $square] = $this->claimableCard();

        $this->claim($event, $square);

        $this->assertSame('PENDING', BingoCompletion::firstOrFail()->status);
    }

    #[Test]
    public function a_proven_name_keeps_the_shortcut(): void
    {
        Setting::set('runelite_plugin_mode', 'live');
        $this->player->forceFill(['osrs_proven_at' => now(), 'osrs_proven_via' => 'runelite'])->save();
        [$event, $square] = $this->claimableCard();

        $this->claim($event, $square);

        $this->assertSame('APPROVED', BingoCompletion::firstOrFail()->status);
    }

    /**
     * Nothing changes until the plugin is live. There is no point holding a
     * claim for a proof nobody can obtain yet, and switching this on under a
     * host who deliberately turned review off would queue everything they
     * stopped queueing.
     */
    #[Test]
    public function an_unproven_name_costs_nothing_while_the_plugin_is_not_live(): void
    {
        foreach (['off', 'testing'] as $mode) {
            BingoCompletion::query()->delete();
            Setting::set('runelite_plugin_mode', $mode);
            [$event, $square] = $this->claimableCard();

            $this->claim($event, $square);

            $this->assertSame('APPROVED', BingoCompletion::firstOrFail()->status, "mode {$mode}");
        }
    }

    /**
     * The notice in the claim dialog reads one boolean, so it can never say
     * something different from what ReviewsClaims stamps.
     */
    #[Test]
    public function every_page_is_told_whether_a_claim_would_go_to_a_host(): void
    {
        Setting::set('runelite_plugin_mode', 'live');

        $this->actingAs($this->player)
            ->get('/events')
            ->assertInertia(fn ($page) => $page->where('auth.user.needsOsrsProof', true));

        $this->player->forceFill(['osrs_proven_at' => now()])->save();

        $this->actingAs($this->player)
            ->get('/events')
            ->assertInertia(fn ($page) => $page->where('auth.user.needsOsrsProof', false));
    }

    /** Nothing to act on below live, so nothing is said. */
    #[Test]
    public function the_notice_stays_quiet_while_the_plugin_is_not_live(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');

        $this->actingAs($this->player)
            ->get('/events')
            ->assertInertia(fn ($page) => $page->where('auth.user.needsOsrsProof', false));
    }

    #[Test]
    public function the_name_gate_says_what_an_unproven_name_costs(): void
    {
        Setting::set('runelite_plugin_mode', 'live');
        $newcomer = User::factory()->create(['osrs_username' => null]);

        $this->actingAs($newcomer)
            ->get('/welcome/osrs-username')
            ->assertInertia(fn ($page) => $page->where('proofMatters', true));

        Setting::set('runelite_plugin_mode', 'testing');

        $this->actingAs($newcomer)
            ->get('/welcome/osrs-username')
            ->assertInertia(fn ($page) => $page->where('proofMatters', false));
    }

    #[Test]
    public function the_board_and_the_card_answer_the_same_way(): void
    {
        Setting::set('runelite_plugin_mode', 'live');
        $settings = ['requires_approval' => false, 'trust_runelite_completions' => true];
        $unproven = new User;
        $proven = (new User)->forceFill(['osrs_proven_at' => now()]);

        foreach ([new Board($settings), new BingoCard($settings)] as $model) {
            $this->assertSame('PENDING', $model->initialClaimStatus('MANUAL', $unproven));
            $this->assertSame('PENDING', $model->initialClaimStatus('RUNELITE', $unproven));
            $this->assertSame('APPROVED', $model->initialClaimStatus('MANUAL', $proven));
            $this->assertSame('APPROVED', $model->initialClaimStatus('RUNELITE', $proven));
        }
    }
}
