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
 * The OSRS Wiki picker behind the tile and bingo-square editors.
 *
 * Reported as "the wiki search API is broken". It was not broken — it had
 * never been built: both editors searched the local `tasks` table, which
 * ships with fourteen rows, while the home page advertised wiki search.
 *
 * MediaWiki is faked throughout (TestCase calls Http::preventStrayRequests(),
 * so an un-faked call fails loudly rather than reaching a volunteer-run wiki
 * from a test run).
 */
class WikiSearchTest extends TestCase
{
    use RefreshDatabase;

    private const WIKI = 'oldschool.runescape.wiki/*';

    protected function setUp(): void
    {
        parent::setUp();

        // The service caches for a day, and a cache shared between tests
        // would let one test's fake answer another test's assertion.
        Cache::flush();
    }

    private function fakeWiki(array $pages): void
    {
        Http::fake([self::WIKI => Http::response(['query' => ['pages' => $pages]])]);
    }

    private function page(int $id, string $title, ?string $thumb = null): array
    {
        return [
            'pageid' => $id,
            'title' => $title,
            'index' => 1,
            'canonicalurl' => 'https://oldschool.runescape.wiki/w/'.str_replace(' ', '_', $title),
            ...($thumb ? ['thumbnail' => ['source' => $thumb]] : []),
        ];
    }

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

    #[Test]
    public function a_host_can_search_the_wiki(): void
    {
        $this->fakeWiki([$this->page(44127, 'Zulrah', 'https://oldschool.runescape.wiki/images/zulrah.png')]);

        [$user, $event] = $this->host();

        $this->actingAs($user)
            ->getJson("/events/{$event->id}/wiki/search?search=zulrah")
            ->assertOk()
            ->assertJsonPath('results.0.title', 'Zulrah')
            ->assertJsonPath('results.0.page_id', 44127);
    }

    /**
     * Gated on canEditEvent, not on the global canCreateTiles permission —
     * otherwise a board's own owner could not fill in their own board unless
     * they also held the EDITOR role, which is the exact shape of bug the
     * team permissions had.
     */
    #[Test]
    public function somebody_who_cannot_edit_the_event_cannot_search(): void
    {
        Http::fake();

        [, $event] = $this->host();

        $outsider = User::factory()->create(['osrs_username' => 'Nosy']);

        $this->actingAs($outsider)
            ->getJson("/events/{$event->id}/wiki/search?search=zulrah")
            ->assertForbidden();
    }

    #[Test]
    public function picking_a_result_creates_a_task_once(): void
    {
        $this->fakeWiki([$this->page(44127, 'Zulrah', 'https://oldschool.runescape.wiki/images/zulrah.png')]);

        [$user, $event] = $this->host();

        $first = $this->actingAs($user)
            ->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 44127, 'title' => 'Zulrah'])
            ->assertOk()
            ->json('task');

        $second = $this->actingAs($user)
            ->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 44127, 'title' => 'Zulrah'])
            ->assertOk()
            ->json('task');

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(1, Task::where('wiki_page_id', 44127)->count());
        $this->assertSame('https://oldschool.runescape.wiki/images/zulrah.png', $first['icon_url']);
    }

    /**
     * The icon comes from the live result, never from the request body —
     * otherwise anyone who can edit a board could point a task's icon at any
     * host they like.
     */
    #[Test]
    public function a_submitted_icon_url_is_ignored_in_favour_of_the_wikis_own(): void
    {
        $this->fakeWiki([$this->page(44127, 'Zulrah', 'https://oldschool.runescape.wiki/images/zulrah.png')]);

        [$user, $event] = $this->host();

        $task = $this->actingAs($user)->postJson("/events/{$event->id}/wiki/tasks", [
            'page_id' => 44127,
            'title' => 'Zulrah',
            'icon_url' => 'https://evil.example/tracker.png',
            'url' => 'https://evil.example',
        ])->assertOk()->json('task');

        $this->assertStringContainsString('runescape.wiki', $task['icon_url']);
        $this->assertStringNotContainsString('evil.example', $task['icon_url']);
    }

    /** A page id that isn't in the live results is not a page we can trust. */
    #[Test]
    public function an_unverifiable_page_is_refused(): void
    {
        $this->fakeWiki([$this->page(44127, 'Zulrah')]);

        [$user, $event] = $this->host();

        $this->actingAs($user)
            ->postJson("/events/{$event->id}/wiki/tasks", ['page_id' => 999999, 'title' => 'Zulrah'])
            ->assertStatus(422);

        $this->assertSame(0, Task::whereNotNull('wiki_page_id')->count());
    }

    /**
     * The picker sits beside a field you can still type into by hand, so an
     * outage should cost suggestions rather than the ability to fill the form
     * in. Empty list, 200, no exception.
     */
    #[Test]
    public function a_wiki_outage_returns_an_empty_list_rather_than_an_error(): void
    {
        Http::fake([self::WIKI => Http::response('', 503)]);

        [$user, $event] = $this->host();

        $this->actingAs($user)
            ->getJson("/events/{$event->id}/wiki/search?search=zulrah")
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    /** An empty term is not a search; it should never leave the app. */
    #[Test]
    public function an_empty_search_never_reaches_the_wiki(): void
    {
        Http::fake();

        [$user, $event] = $this->host();

        $this->actingAs($user)->getJson("/events/{$event->id}/wiki/search?search=")->assertOk();

        Http::assertNothingSent();
    }
}
