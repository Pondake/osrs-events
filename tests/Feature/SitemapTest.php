<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The sitemap must list what a visitor can actually open, and nothing else.
 * A URL in here that redirects to a login wastes crawl budget and puts an
 * unopenable page in the index.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_serves_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<urlset', false);
    }

    #[Test]
    public function it_lists_the_public_landing_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        foreach (['/osrs-snakes-and-ladders', '/osrs-clan-events', '/osrs-event-ideas', '/osrs-bingo', '/osrs-skill-race', '/osrs-drop-race', '/events'] as $path) {
            $response->assertSee(url($path), false);
        }
    }

    #[Test]
    public function it_lists_published_cms_pages(): void
    {
        Page::create(['slug' => 'about', 'title' => 'About', 'is_published' => true, 'blocks' => []]);

        $this->get('/sitemap.xml')->assertSee(url('/about'), false);
    }

    #[Test]
    public function it_omits_unpublished_pages(): void
    {
        Page::create(['slug' => 'secret', 'title' => 'Secret', 'is_published' => false, 'blocks' => []]);

        $this->get('/sitemap.xml')->assertDontSee(url('/secret'), false);
    }

    /** A partial page's row backs a URL already listed; /home itself 404s. */
    #[Test]
    public function it_omits_partial_page_rows(): void
    {
        Page::create(['slug' => 'home', 'title' => 'Home', 'is_published' => true, 'blocks' => []]);

        $this->get('/sitemap.xml')->assertDontSee(url('/home'), false);
    }

    #[Test]
    public function it_omits_everything_behind_a_login(): void
    {
        $response = $this->get('/sitemap.xml');

        foreach (['/my-events', '/admin', '/settings/profile', '/welcome/osrs-username'] as $path) {
            $response->assertDontSee(url($path), false);
        }
    }
}
