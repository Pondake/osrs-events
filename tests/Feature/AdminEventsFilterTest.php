<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `/admin/events` used to dump every event ever created in one flat list with
 * no way to narrow it — reported as missing entirely once there were more
 * than a handful of events to scroll past.
 */
class AdminEventsFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    private function event(array $attributes = []): Event
    {
        // paused_at is deliberately absent from Event::$fillable (see the
        // model's own comment: pausing is its own action, not a field
        // slipped into an ordinary create/update array) — set directly on
        // the instance rather than through create()'s mass assignment, which
        // silently drops it.
        $pausedAt = $attributes['paused_at'] ?? null;
        unset($attributes['paused_at']);

        $event = Event::create(array_merge([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ], $attributes));

        if ($pausedAt) {
            $event->paused_at = $pausedAt;
            $event->save();
        }

        return $event;
    }

    #[Test]
    public function search_narrows_by_title(): void
    {
        $this->event(['title' => 'Summer Bingo']);
        $this->event(['title' => 'Winter Race']);

        $boards = $this->actingAs($this->admin())
            ->get('/admin/events?search=bingo')
            ->viewData('page')['props']['boards'];

        $this->assertCount(1, $boards);
        $this->assertSame('Summer Bingo', $boards[0]['title']);
    }

    #[Test]
    public function status_active_excludes_paused_and_deleted(): void
    {
        $active = $this->event(['title' => 'Active']);
        $paused = $this->event(['title' => 'Paused', 'paused_at' => now()]);
        $deleted = $this->event(['title' => 'Deleted']);
        $deleted->delete();

        $titles = collect($this->actingAs($this->admin())
            ->get('/admin/events?status=active')
            ->viewData('page')['props']['boards'])->pluck('title');

        $this->assertTrue($titles->contains('Active'));
        $this->assertFalse($titles->contains('Paused'));
        $this->assertFalse($titles->contains('Deleted'));
    }

    #[Test]
    public function status_paused_shows_only_paused(): void
    {
        $this->event(['title' => 'Active']);
        $this->event(['title' => 'Paused', 'paused_at' => now()]);

        $titles = collect($this->actingAs($this->admin())
            ->get('/admin/events?status=paused')
            ->viewData('page')['props']['boards'])->pluck('title');

        $this->assertSame(['Paused'], $titles->all());
    }

    #[Test]
    public function status_deleted_shows_only_deleted(): void
    {
        $this->event(['title' => 'Active']);
        $deleted = $this->event(['title' => 'Gone']);
        $deleted->delete();

        $titles = collect($this->actingAs($this->admin())
            ->get('/admin/events?status=deleted')
            ->viewData('page')['props']['boards'])->pluck('title');

        $this->assertSame(['Gone'], $titles->all());
    }

    /** A query string nobody meant to type by hand falls back to 'all', not to nothing. */
    #[Test]
    public function an_unrecognised_status_falls_back_to_all(): void
    {
        $this->event(['title' => 'Active']);
        $paused = $this->event(['title' => 'Paused', 'paused_at' => now()]);

        $props = $this->actingAs($this->admin())->get('/admin/events?status=nonsense')->viewData('page')['props'];

        $this->assertSame('all', $props['filters']['status']);
        $this->assertCount(2, $props['boards']);
    }

    #[Test]
    public function search_and_status_combine(): void
    {
        $this->event(['title' => 'Bingo Active']);
        $this->event(['title' => 'Bingo Paused', 'paused_at' => now()]);
        $this->event(['title' => 'Race Active']);

        $titles = collect($this->actingAs($this->admin())
            ->get('/admin/events?search=bingo&status=active')
            ->viewData('page')['props']['boards'])->pluck('title');

        $this->assertSame(['Bingo Active'], $titles->all());
    }

    #[Test]
    public function a_non_admin_cannot_reach_the_list_at_all(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Pondake']);

        $this->actingAs($player)->get('/admin/events')->assertForbidden();
    }
}
