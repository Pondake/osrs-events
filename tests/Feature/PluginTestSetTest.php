<?php

namespace Tests\Feature;

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PluginCompletion;
use App\Models\PluginToken;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TargetProgress;
use App\Models\Task;
use App\Models\User;
use App\Services\BingoService;
use App\Services\PluginTestReport;
use App\Support\PluginTestSet;
use Database\Seeders\PluginTestSetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginTestSetTest extends TestCase
{
    use RefreshDatabase;

    private User $tester;

    private string $code;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('runelite_plugin_mode', 'testing');
        (new PluginTestSetSeeder)->run();
        $this->event = Event::where('title', PluginTestSet::EVENT_TITLE)->firstOrFail();

        $this->tester = User::factory()->create(['osrs_username' => 'Iron Sample']);
        $this->code = PluginToken::issueFor($this->tester);
        EventParticipant::create(['event_id' => $this->event->id, 'user_id' => $this->tester->id]);
    }

    private function report(string $kind, string $name, array $context = [], int $quantity = 1, string $version = '0.0.4'): void
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->code}", 'X-Plugin-Version' => $version])
            ->postJson('/api/plugin/v1/completions', [
                'client_event_id' => (string) str()->uuid(),
                'kind' => $kind,
                'name' => $name,
                'quantity' => $quantity,
                'rsn' => 'Iron Sample',
                'occurred_at' => now()->toIso8601String(),
                'context' => ['source' => 'npc_kill', 'npc_id' => 1, 'npc_name' => 'Cow', 'npc_level' => 2, 'region_id' => 12850, 'items' => [['id' => 1739, 'name' => 'Cowhide', 'quantity' => 1]], ...$context],
            ])
            ->assertCreated();
    }

    private function scenario(string $key): array
    {
        return collect(app(PluginTestReport::class)->forUser($this->tester->fresh())['scenarioDetails'])->firstWhere('key', $key);
    }

    #[Test]
    public function the_version_header_is_stored_on_the_report_and_the_code(): void
    {
        $this->report('npc_kill', 'Goblin', version: '0.0.4');

        $this->assertSame('0.0.4', PluginCompletion::first()->plugin_version);
        $this->assertSame('0.0.4', PluginToken::first()->last_plugin_version);
    }

    #[Test]
    public function a_malformed_version_is_stored_as_nothing_and_never_refused(): void
    {
        $this->report('npc_kill', 'Goblin', version: '<script>');

        $this->assertNull(PluginCompletion::first()->plugin_version);
    }

    #[Test]
    public function a_scenario_is_missing_until_its_reports_arrive_and_ok_after(): void
    {
        $this->assertSame('missing', $this->scenario('goblin')['status']);

        $this->report('npc_kill', 'Goblin');

        $this->assertSame('ok', $this->scenario('goblin')['status']);
    }

    #[Test]
    public function a_report_without_the_required_context_is_partial_and_says_which_field(): void
    {
        $this->report('npc_kill', 'Goblin', ['region_id' => null]);

        $scenario = $this->scenario('goblin');
        $this->assertSame('partial', $scenario['status']);
        $this->assertSame(['region_id'], $scenario['expectations'][0]['problems'][0]['fields']);
    }

    #[Test]
    public function a_stack_below_the_bar_is_partial_and_the_full_one_passes(): void
    {
        $this->report('item', 'Feather', quantity: 5);
        $this->assertSame('partial', $this->scenario('feathers')['status']);

        $this->report('item', 'Feather', quantity: 15);
        $this->assertSame('ok', $this->scenario('feathers')['status']);
    }

    #[Test]
    public function three_cows_count_up_and_claim_on_the_third(): void
    {
        $this->report('item', 'Cowhide');
        $this->report('npc_kill', 'Cow');
        $this->report('npc_kill', 'Cow');

        $this->assertSame('partial', $this->scenario('cows')['status']);

        $this->report('npc_kill', 'Cow');

        $this->assertSame('ok', $this->scenario('cows')['status']);
    }

    #[Test]
    public function every_report_is_in_the_log_whether_a_scenario_expects_it_or_not(): void
    {
        $this->report('npc_kill', 'Goblin');
        $this->travel(1)->seconds();
        $this->report('item', 'Raw beef');

        $this->assertSame(['Raw beef', 'Goblin'], collect(app(PluginTestReport::class)->forUser($this->tester)['log'])->pluck('name')->all());
    }

    #[Test]
    public function a_scenario_passes_on_a_testers_own_event_too(): void
    {
        $outsider = User::factory()->create(['osrs_username' => 'Main Sample']);
        $this->code = PluginToken::issueFor($outsider);
        $this->tester = $outsider;

        $event = Event::create(['title' => 'Own card', 'type' => 'BINGO', 'mode' => 'SOLO', 'access_mode' => 'OPEN', 'is_listed' => false]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $outsider->id]);
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        $card->squares()->where('position', 0)->update(['task_id' => Task::where('title', 'Goblin')->value('id')]);

        $this->withHeaders(['Authorization' => "Bearer {$this->code}", 'X-Plugin-Version' => '0.0.4'])
            ->postJson('/api/plugin/v1/completions', [
                'client_event_id' => (string) str()->uuid(),
                'kind' => 'npc_kill',
                'name' => 'Goblin',
                'quantity' => 1,
                'rsn' => 'Main Sample',
                'occurred_at' => now()->toIso8601String(),
                'context' => ['source' => 'npc_kill', 'npc_id' => 3029, 'npc_level' => 2, 'region_id' => 12850],
            ])
            ->assertCreated();

        $this->assertSame('ok', $this->scenario('goblin')['status']);
    }

    #[Test]
    public function starting_over_clears_only_this_testers_squares_and_counts(): void
    {
        $other = User::factory()->create(['osrs_username' => 'Main Sample']);
        EventParticipant::create(['event_id' => $this->event->id, 'user_id' => $other->id]);
        $square = $this->event->bingoCard->squares()->first();
        BingoCompletion::create(['bingo_square_id' => $square->id, 'user_id' => $other->id, 'marked_by' => $other->id, 'status' => 'APPROVED']);

        $this->report('npc_kill', 'Goblin');
        $this->report('npc_kill', 'Cow');
        $this->assertSame(1, BingoCompletion::where('user_id', $this->tester->id)->count());
        $this->assertSame(1, TargetProgress::count());

        $this->travel(1)->minutes();
        $this->actingAs($this->tester)->post('/settings/runelite/test-reset')->assertRedirect();

        $this->assertSame(0, BingoCompletion::where('user_id', $this->tester->id)->count());
        $this->assertSame(1, BingoCompletion::where('user_id', $other->id)->count());
        $this->assertSame(0, TargetProgress::count());
        $this->assertSame('missing', $this->scenario('goblin')['status']);
    }

    #[Test]
    public function starting_over_does_not_exist_outside_testing(): void
    {
        Setting::set('runelite_plugin_mode', 'live');

        $this->actingAs($this->tester)->post('/settings/runelite/test-reset')->assertNotFound();
    }

    #[Test]
    public function the_checklist_is_on_the_settings_page_only_while_testing(): void
    {
        $this->actingAs($this->tester)->get('/settings/runelite')
            ->assertInertia(fn ($page) => $page->where('tests.testSet.joined', true)->has('tests.scenarioDetails', count(PluginTestSet::scenarios())));

        Setting::set('runelite_plugin_mode', 'live');

        $this->actingAs($this->tester)->get('/settings/runelite')
            ->assertInertia(fn ($page) => $page->where('tests', null));
    }

    #[Test]
    public function the_admin_sees_every_account_with_the_plugin_not_only_the_test_set(): void
    {
        $this->actingAs($this->tester)->get('/admin/plugin-tests')->assertForbidden();

        $outsider = User::factory()->create(['osrs_username' => 'Main Sample']);
        PluginToken::issueFor($outsider);
        $admin = User::factory()->create(['osrs_username' => 'Admin Sample']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->get("/admin/plugin-tests?tester={$outsider->id}")
            ->assertInertia(fn ($page) => $page->component('Admin/PluginTests')
                ->has('testers', 2)
                ->where('selected.user.id', $outsider->id)
                ->where('selected.testSet.joined', false));
    }

    #[Test]
    public function the_admin_sees_nothing_once_the_plugin_is_live(): void
    {
        Setting::set('runelite_plugin_mode', 'live');
        $admin = User::factory()->create(['osrs_username' => 'Admin Sample']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->get('/admin/plugin-tests')
            ->assertInertia(fn ($page) => $page->has('testers', 0)->where('selected', null));
    }
}
