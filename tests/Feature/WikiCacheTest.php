<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The task table as a cache of the OSRS Wiki.
 *
 * It used to be a library people were asked to search alongside the wiki —
 * fourteen seeded rows presented as a peer of the whole thing. Now the picker
 * searches the wiki only, and every page it uses lands here so the second
 * person to want "Zulrah" costs the wiki nothing.
 *
 * A cache needs an age, which is what these pin down: a hand-written task
 * never goes stale, a wiki-sourced one does after a week, and re-picking a
 * page corrects a title or an image that changed upstream.
 */
class WikiCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /** @return array{0: User, 1: Event} */
    private function host(): array
    {
        $user = User::factory()->create(['osrs_username' => 'Host']);

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true]);

        return [$user, $event];
    }

    /** One page, as MediaWiki's formatversion=2 shape. */
    private function page(string $title, ?string $thumb): array
    {
        return ['query' => ['pages' => [[
            'pageid' => 44127,
            'title' => $title,
            'index' => 1,
            'canonicalurl' => 'https://oldschool.runescape.wiki/w/'.str_replace(' ', '_', $title),
            ...($thumb ? ['thumbnail' => ['source' => $thumb]] : []),
        ]]]];
    }

    private function fakeWiki(string $title, ?string $thumb): void
    {
        Http::fake(['oldschool.runescape.wiki/*' => Http::response($this->page($title, $thumb))]);
    }

    #[Test]
    public function a_hand_written_task_is_never_stale(): void
    {
        $task = Task::create(['title' => 'Kill 50 cows']);

        $this->assertFalse($task->wikiCacheIsStale());
    }

    #[Test]
    public function a_wiki_task_goes_stale_after_a_week(): void
    {
        $fresh = Task::create(['title' => 'Zulrah', 'wiki_page_id' => 1, 'wiki_synced_at' => now()->subDays(6)]);
        $stale = Task::create(['title' => 'Vorkath', 'wiki_page_id' => 2, 'wiki_synced_at' => now()->subDays(8)]);

        $this->assertFalse($fresh->wikiCacheIsStale());
        $this->assertTrue($stale->wikiCacheIsStale());
    }

    /** An import that has never been dated cannot be trusted either. */
    #[Test]
    public function a_wiki_task_with_no_sync_date_is_stale(): void
    {
        $task = Task::create(['title' => 'Zulrah', 'wiki_page_id' => 1, 'wiki_synced_at' => null]);

        $this->assertTrue($task->wikiCacheIsStale());
    }

    #[Test]
    public function importing_stamps_the_sync_time(): void
    {
        $this->fakeWiki('Zulrah', 'https://oldschool.runescape.wiki/images/zulrah.png');

        [$user, $event] = $this->host();

        $this->actingAs($user)
            ->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 44127, 'title' => 'Zulrah'])
            ->assertOk();

        $this->assertNotNull(Task::where('wiki_page_id', 44127)->value('wiki_synced_at'));
    }

    /**
     * The point of a cache with an age: a page that has been renamed or
     * re-illustrated upstream corrects itself the next time somebody picks
     * it, instead of showing last spring's title forever.
     */
    #[Test]
    public function re_picking_a_page_refreshes_the_cached_row(): void
    {
        [$user, $event] = $this->host();

        // A sequence, not two Http::fake() calls: a second fake() ADDS a stub
        // rather than replacing the first, and the earliest matching one
        // wins — so the "new" response would never have been served and the
        // test would have proved nothing.
        Http::fake(['oldschool.runescape.wiki/*' => Http::sequence()
            ->push($this->page('Zulrah', 'https://oldschool.runescape.wiki/images/old.png'))
            ->push($this->page('Zulrah (serpentine)', 'https://oldschool.runescape.wiki/images/new.png'))]);

        $this->actingAs($user)->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 44127, 'title' => 'Zulrah'])->assertOk();

        $id = Task::where('wiki_page_id', 44127)->value('id');

        Cache::flush();
        $this->actingAs($user)->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 44127, 'title' => 'Zulrah (serpentine)'])->assertOk();

        $task = Task::where('wiki_page_id', 44127)->firstOrFail();

        // Same row, corrected — not a second one alongside the first.
        $this->assertSame($id, $task->id);
        $this->assertSame('Zulrah (serpentine)', $task->title);
        $this->assertStringContainsString('new.png', $task->icon_url);
        $this->assertSame(1, Task::where('wiki_page_id', 44127)->count());
    }
}
