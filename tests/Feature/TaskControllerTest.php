<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Deleting a task used to be instant and irreversible — no confirm, no
 * undo — reported live as "Gaat instant!". This pins the soft-delete-backed
 * undo that replaced it: a tile using the task keeps pointing at it the
 * whole time it's "deleted", so restoring is a complete undo rather than a
 * same-title task with none of its old links back.
 */
class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        $user = User::factory()->create(['osrs_username' => 'Editor']);
        $user->givePermissionTo(Permission::findOrCreate('canCreateTiles', 'web'));

        return $user;
    }

    #[Test]
    public function deleting_a_task_hides_it_without_severing_a_tiles_link_to_it(): void
    {
        $editor = $this->editor();
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);

        $tile = Tile::create(['board_id' => $this->boardId(), 'position' => 0, 'task_id' => $task->id]);

        $this->actingAs($editor)->delete("/admin/tasks/{$task->id}")->assertRedirect();

        $this->assertNotNull(Task::withTrashed()->find($task->id)->deleted_at);
        // Not nulled by the FK's nullOnDelete — that only fires on a real
        // DELETE, and this was a soft one.
        $this->assertSame($task->id, $tile->fresh()->task_id);
        // But invisible through the normal relation while soft-deleted,
        // same as before this change — a deleted task still reads as gone.
        $this->assertNull($tile->fresh()->task);
    }

    #[Test]
    public function a_deleted_task_does_not_appear_in_the_index(): void
    {
        $editor = $this->editor();
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);

        $this->actingAs($editor)->delete("/admin/tasks/{$task->id}");

        $tasks = $this->actingAs($editor)->get('/admin/tasks')->viewData('page')['props']['tasks'];

        $this->assertEmpty(collect($tasks)->where('id', $task->id));
    }

    #[Test]
    public function restoring_relinks_every_tile_that_was_using_it(): void
    {
        $editor = $this->editor();
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);
        $tile = Tile::create(['board_id' => $this->boardId(), 'position' => 0, 'task_id' => $task->id]);

        $this->actingAs($editor)->delete("/admin/tasks/{$task->id}");
        $this->actingAs($editor)->post("/admin/tasks/{$task->id}/restore")->assertRedirect();

        $this->assertNull(Task::find($task->id)->deleted_at);
        $this->assertSame($task->id, $tile->fresh()->task->id);
    }

    #[Test]
    public function an_optional_note_is_recorded_on_the_audit_log(): void
    {
        $editor = $this->editor();
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);

        $this->actingAs($editor)->delete("/admin/tasks/{$task->id}", ['note' => 'duplicate of another task']);

        $entry = AuditLog::where('action', 'task.deleted')->latest()->first();

        $this->assertSame('duplicate of another task', $entry->metadata['note']);
    }

    #[Test]
    public function no_note_is_recorded_when_none_is_given(): void
    {
        $editor = $this->editor();
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);

        $this->actingAs($editor)->delete("/admin/tasks/{$task->id}");

        $entry = AuditLog::where('action', 'task.deleted')->latest()->first();

        $this->assertNull($entry->metadata);
    }

    #[Test]
    public function without_the_permission_neither_route_is_reachable(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Pondake']);
        $task = Task::create(['id' => (string) str()->uuid(), 'title' => 'Kill Zulrah']);

        $this->actingAs($player)->delete("/admin/tasks/{$task->id}")->assertForbidden();

        $this->actingAs($player)->post("/admin/tasks/{$task->id}/restore")->assertForbidden();
    }

    /** A minimal board to hang a tile off — the task delete/restore behavior is what's under test, not board setup. */
    private function boardId(): string
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $event = \App\Models\Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $board = $event->board()->create(['size' => 'SIZE_5X5']);

        return $board->id;
    }
}
