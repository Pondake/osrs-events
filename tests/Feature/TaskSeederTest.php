<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The catalogue is reference data, so it has to be runnable on production —
 * which means bringing nothing else with it, and surviving a second run.
 */
class TaskSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_tasks_and_nothing_else(): void
    {
        $this->seed(TaskSeeder::class);

        $this->assertGreaterThan(50, Task::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Event::count());
    }

    #[Test]
    public function running_it_twice_does_not_duplicate_a_task(): void
    {
        $this->seed(TaskSeeder::class);
        $first = Task::orderBy('title')->first();
        $count = Task::count();

        $this->seed(TaskSeeder::class);

        $this->assertSame($count, Task::count());
        $this->assertSame($first->id, Task::orderBy('title')->first()->id);
    }

    /** Every tile already pointing at a task must keep pointing at it. */
    #[Test]
    public function a_reseed_keeps_the_row_a_tile_is_attached_to(): void
    {
        $this->seed(TaskSeeder::class);
        $task = Task::orderBy('title')->first();
        $task->update(['description' => 'Edited by a host']);

        $this->seed(TaskSeeder::class);

        $this->assertSame($task->id, Task::find($task->id)?->id);
    }
}
