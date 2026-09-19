<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * After the task seeder `/admin/tasks` runs into the hundreds of rows, and a
 * task without a wiki link has no explanation to point at from the tile card
 * or the claim dialog — so the gaps have to be findable.
 */
class AdminTaskFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function titles(string $query)
    {
        return collect($this->actingAs($this->admin())
            ->get('/admin/tasks'.$query)
            ->viewData('page')['props']['tasks'])->pluck('title');
    }

    private function seedTasks(): void
    {
        Task::create(['title' => 'Cow', 'wiki_url' => 'https://oldschool.runescape.wiki/w/Cow', 'icon_url' => 'https://example.test/cow.png']);
        Task::create(['title' => 'Cow hide', 'wiki_url' => null, 'icon_url' => null]);
        // The empty-string case: nothing in the schema stops one, and a row
        // written outside a request never passes through
        // ConvertEmptyStringsToNull.
        Task::create(['title' => 'Cowardly sheep', 'wiki_url' => '', 'icon_url' => '']);
    }

    #[Test]
    public function no_filter_shows_every_task(): void
    {
        $this->seedTasks();

        $this->assertSame(['Cow', 'Cow hide', 'Cowardly sheep'], $this->titles('')->all());
    }

    #[Test]
    public function with_wiki_shows_only_tasks_that_have_a_link(): void
    {
        $this->seedTasks();

        $this->assertSame(['Cow'], $this->titles('?filter=with_wiki')->all());
    }

    #[Test]
    public function without_wiki_counts_an_empty_string_as_missing(): void
    {
        $this->seedTasks();

        $this->assertSame(['Cow hide', 'Cowardly sheep'], $this->titles('?filter=without_wiki')->all());
    }

    #[Test]
    public function without_icon_counts_an_empty_string_as_missing(): void
    {
        $this->seedTasks();

        $this->assertSame(['Cow hide', 'Cowardly sheep'], $this->titles('?filter=without_icon')->all());
    }

    #[Test]
    public function the_filter_and_a_search_term_narrow_together(): void
    {
        $this->seedTasks();
        Task::create(['title' => 'Shrimp', 'wiki_url' => null, 'icon_url' => null]);

        $this->assertSame(['Cow hide', 'Cowardly sheep'], $this->titles('?filter=without_wiki&search=cow')->all());
        $this->assertSame(['Cow'], $this->titles('?filter=with_wiki&search=cow')->all());
    }

    /** A query string nobody meant to type by hand shows everything, not nothing. */
    #[Test]
    public function an_unrecognised_filter_falls_back_to_all(): void
    {
        $this->seedTasks();

        $this->assertCount(3, $this->titles('?filter=nonsense'));
    }
}
