<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The guide pages' FAQ used to be CMS-editable (a `pages` row backing
 * FAQPage structured data); that was dropped when the pages were rebuilt
 * away from `u-page-section`'s landing-page layout onto a static,
 * wiki-style one — see LandingController::snakesAndLadders()'s own comment.
 * These tests now guard the opposite thing: a stray or leftover `pages` row
 * for one of these slugs (an environment that ran the old seeder still has
 * one) must NOT leak into the page or its structured data.
 */
class LandingPageFaqTest extends TestCase
{
    use RefreshDatabase;

    public static function guidePaths(): array
    {
        return [
            'snakes and ladders' => ['/osrs-snakes-and-ladders', 'osrs-snakes-and-ladders'],
            'clan events' => ['/osrs-clan-events', 'osrs-clan-events'],
            'event ideas' => ['/osrs-event-ideas', 'osrs-event-ideas'],
            'bingo' => ['/osrs-bingo', 'osrs-bingo'],
            'skill race' => ['/osrs-skill-race', 'osrs-skill-race'],
            'drop race' => ['/osrs-drop-race', 'osrs-drop-race'],
        ];
    }

    #[Test]
    #[DataProvider('guidePaths')]
    public function a_leftover_page_row_for_the_slug_is_ignored(string $path, string $slug): void
    {
        Page::create([
            'slug' => $slug,
            'title' => 'A leftover row',
            'is_published' => true,
            'blocks' => [['type' => 'faq', 'props' => ['items' => [
                ['question' => 'A CMS question that should never render?', 'answer' => 'A CMS answer that should never render.'],
            ]]]],
        ]);

        $this->get($path)
            ->assertOk()
            ->assertDontSee('A CMS question that should never render?');
    }

    #[Test]
    #[DataProvider('guidePaths')]
    public function the_page_itself_still_renders_without_any_row(string $path): void
    {
        $this->get($path)->assertOk();
    }

    /** The row backs nothing now; it must not also become a page of its own. */
    #[Test]
    public function the_landing_slugs_are_not_served_by_the_catch_all(): void
    {
        Page::create(['slug' => 'osrs-event-ideas', 'title' => 'Not this', 'is_published' => true, 'blocks' => []]);

        $this->get('/osrs-event-ideas')->assertOk();
        $this->assertContains('osrs-event-ideas', Page::PARTIAL_SLUGS);
    }
}
