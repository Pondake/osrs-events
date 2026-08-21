<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Event blueprints — the reusable formats the create form suggests.
 *
 * The rules worth holding: only board creators manage them, only active ones
 * are suggested, and a blueprint can never carry a type/metric pairing the
 * create form would then reject — because that would look like the blueprint
 * broke the form.
 */
class EventBlueprintTest extends TestCase
{
    use RefreshDatabase;

    private function creator(string $name = 'Host'): User
    {
        $user = User::factory()->create(['osrs_username' => $name]);

        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $user->assignRole($role);

        return $user;
    }

    private function player(): User
    {
        $user = User::factory()->create(['osrs_username' => 'JustAPlayer']);
        $user->assignRole(Role::findOrCreate('PLAYER', 'web'));

        return $user;
    }

    // --------------------------------------------------------- suggestions

    #[Test]
    public function the_autocomplete_lists_active_blueprints(): void
    {
        EventBlueprint::create(['title' => 'Skill of the Week', 'type' => 'SKILL_RACE', 'metric' => 'overall']);

        $this->actingAs($this->creator())
            ->getJson('/event-blueprints')
            ->assertOk()
            ->assertJsonPath('blueprints.0.title', 'Skill of the Week');
    }

    #[Test]
    public function a_retired_blueprint_is_not_suggested(): void
    {
        EventBlueprint::create(['title' => 'Retired Format', 'is_active' => false]);

        $this->assertSame([], $this->actingAs($this->creator())->getJson('/event-blueprints')->json('blueprints'));
    }

    #[Test]
    public function the_autocomplete_filters_by_the_search_term(): void
    {
        EventBlueprint::create(['title' => 'Skill of the Week']);
        EventBlueprint::create(['title' => 'Boss of the Month']);

        $titles = collect($this->actingAs($this->creator())->getJson('/event-blueprints?search=boss')->json('blueprints'))
            ->pluck('title');

        $this->assertSame(['Boss of the Month'], $titles->all());
    }

    /** An empty search is the list, not nothing — that is when it helps most. */
    #[Test]
    public function an_empty_search_returns_everything_active(): void
    {
        EventBlueprint::create(['title' => 'Skill of the Week']);
        EventBlueprint::create(['title' => 'Boss of the Month']);

        $this->assertCount(2, $this->actingAs($this->creator())->getJson('/event-blueprints')->json('blueprints'));
    }

    // -------------------------------------------------------- permissions

    #[Test]
    public function a_plain_player_can_neither_see_nor_manage_them(): void
    {
        $player = $this->player();

        $this->actingAs($player)->getJson('/event-blueprints')->assertForbidden();
        $this->actingAs($player)->get('/admin/blueprints')->assertForbidden();
        $this->actingAs($player)->post('/admin/blueprints', ['title' => 'Sneaky'])->assertForbidden();
    }

    /**
     * Gated on canCreateBoards rather than isAdmin: the people who run
     * events are the people who know which formats get reused, and making
     * this admin-only means the list goes stale.
     */
    #[Test]
    public function a_board_creator_can_manage_them_without_being_an_admin(): void
    {
        $creator = $this->creator();

        $this->actingAs($creator)->get('/admin/blueprints')->assertOk();

        $this->actingAs($creator)->post('/admin/blueprints', [
            'title' => 'Skill of the Week',
            'type' => 'SKILL_RACE',
            'metric' => 'overall',
        ])->assertRedirect();

        $this->assertSame(1, EventBlueprint::where('title', 'Skill of the Week')->count());
    }

    // ---------------------------------------------------------- validation

    /**
     * A blueprint prefills type and metric, so one carrying a pairing the
     * create form rejects would look like the blueprint broke the form.
     */
    #[Test]
    public function a_metric_the_event_form_would_reject_is_refused(): void
    {
        $this->actingAs($this->creator())->post('/admin/blueprints', [
            'title' => 'Nonsense',
            'type' => 'SKILL_RACE',
            'metric' => 'not-a-real-metric',
        ])->assertSessionHasErrors('metric');
    }

    #[Test]
    public function an_unavailable_type_is_refused(): void
    {
        $this->actingAs($this->creator())->post('/admin/blueprints', [
            'title' => 'Quiz night',
            'type' => 'QUIZ',
        ])->assertSessionHasErrors('type');
    }

    /** A title-only blueprint is a legitimate entry, not an incomplete one. */
    #[Test]
    public function a_blueprint_may_carry_nothing_but_a_title(): void
    {
        $this->actingAs($this->creator())
            ->post('/admin/blueprints', ['title' => 'Summer Event'])
            ->assertSessionHasNoErrors();

        $blueprint = EventBlueprint::where('title', 'Summer Event')->firstOrFail();

        $this->assertNull($blueprint->type);
        $this->assertNull($blueprint->metric);
        $this->assertTrue($blueprint->is_active);
    }

    #[Test]
    public function a_title_is_required(): void
    {
        $this->actingAs($this->creator())
            ->post('/admin/blueprints', ['type' => 'BINGO'])
            ->assertSessionHasErrors('title');
    }

    // ------------------------------------------------------------- editing

    #[Test]
    public function a_blueprint_can_be_edited_and_retired(): void
    {
        $blueprint = EventBlueprint::create(['title' => 'Skill of the Week', 'type' => 'SKILL_RACE', 'metric' => 'mining']);

        $this->actingAs($this->creator())->patch("/admin/blueprints/{$blueprint->id}", [
            'title' => 'Skill of the Fortnight',
            'type' => 'SKILL_RACE',
            'metric' => 'slayer',
            'is_active' => false,
        ])->assertRedirect();

        $blueprint = $blueprint->fresh();

        $this->assertSame('Skill of the Fortnight', $blueprint->title);
        $this->assertSame('slayer', $blueprint->metric);
        $this->assertFalse($blueprint->is_active);
    }

    #[Test]
    public function a_blueprint_can_be_deleted(): void
    {
        $blueprint = EventBlueprint::create(['title' => 'Skill of the Week']);

        $this->actingAs($this->creator())->delete("/admin/blueprints/{$blueprint->id}")->assertRedirect();

        $this->assertNull(EventBlueprint::find($blueprint->id));
    }

    // -------------------------------------------------------- the seeder

    /**
     * Keyed on title, so re-running tops the list back up rather than
     * doubling it — the seeder's whole reason for being separately runnable.
     */
    #[Test]
    public function the_seeder_is_safe_to_run_twice(): void
    {
        $this->seed(EventBlueprintSeeder::class);
        $first = EventBlueprint::count();

        $this->seed(EventBlueprintSeeder::class);

        $this->assertGreaterThan(0, $first);
        $this->assertSame($first, EventBlueprint::count());
    }

    /**
     * Every seeded blueprint must survive the create form's own validation,
     * or picking one hands the user an error they did not cause.
     */
    #[Test]
    public function every_seeded_blueprint_carries_a_valid_type_and_metric(): void
    {
        $this->seed(EventBlueprintSeeder::class);

        foreach (EventBlueprint::all() as $blueprint) {
            if ($blueprint->type !== null) {
                $this->assertContains($blueprint->type, Event::availableTypes(), $blueprint->title);
            }

            if ($blueprint->metric !== null) {
                $this->assertContains($blueprint->metric, Event::allMetrics(), $blueprint->title);
            }
        }
    }

    /**
     * And the pairing has to match: a boss on a skill race is refused by the
     * create form, so a blueprint offering one is a trap.
     */
    #[Test]
    public function no_seeded_blueprint_pairs_a_metric_with_the_wrong_type(): void
    {
        $this->seed(EventBlueprintSeeder::class);

        foreach (EventBlueprint::whereNotNull('metric')->get() as $blueprint) {
            $expected = match ($blueprint->type) {
                'SKILL_RACE' => Event::SKILL_METRICS,
                'DROP_RACE' => Event::BOSS_METRICS,
                default => null,
            };

            $this->assertNotNull($expected, "{$blueprint->title} has a metric but a type that takes none");
            $this->assertContains($blueprint->metric, $expected, $blueprint->title);
        }
    }
}
