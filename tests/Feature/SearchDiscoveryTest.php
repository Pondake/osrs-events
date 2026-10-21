<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_tile_ideas_guide_renders_every_tier_with_translated_tiles(): void
    {
        $this->get('/osrs-bingo-tile-ideas')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('OsrsBingoTileIdeas')
                ->has('tips', 5)
                ->has('tiers', 5)
                ->has('faqs', 4)
                ->where('tiers', function ($tiers) {
                    foreach ($tiers as $tier) {
                        $this->assertNotEmpty($tier['tiles']);
                        foreach ([$tier['title'], $tier['intro'], ...array_column($tier['tiles'], 'title'), ...array_column($tier['tiles'], 'description')] as $text) {
                            $this->assertStringNotContainsString('landing.tile_ideas.', $text);
                        }
                    }

                    return true;
                }));
    }

    #[Test]
    public function the_tile_ideas_faq_reaches_the_json_ld(): void
    {
        $this->assertStringContainsString('"@type":"FAQPage"', $this->get('/osrs-bingo-tile-ideas')->getContent());
    }

    #[Test]
    public function the_home_page_identifies_the_site_as_an_organization_and_website(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
    }

    #[Test]
    public function the_sitemap_lists_the_tile_ideas_guide_with_a_lastmod(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertMatchesRegularExpression(
            '#<loc>'.preg_quote(url('/osrs-bingo-tile-ideas'), '#').'</loc>\s*<lastmod>\d{4}-\d{2}-\d{2}</lastmod>#',
            $xml,
        );
    }

    #[Test]
    public function the_indexnow_key_is_404_until_one_is_configured(): void
    {
        config(['services.indexnow.key' => null]);
        $this->get('/indexnow.txt')->assertNotFound();

        config(['services.indexnow.key' => 'test-key-0123456789']);
        $this->get('/indexnow.txt')->assertOk()->assertSeeText('test-key-0123456789');
    }

    #[Test]
    public function the_pre_launch_door_lets_crawlers_reach_the_guide_and_the_key(): void
    {
        Setting::set('site_lock_enabled', true);
        config(['services.indexnow.key' => 'test-key-0123456789']);

        $this->get('/osrs-bingo-tile-ideas')->assertOk();
        $this->get('/indexnow.txt')->assertOk();
    }

    #[Test]
    public function seo_indexnow_submits_the_sitemap_urls(): void
    {
        config(['services.indexnow.key' => 'test-key-0123456789']);
        Http::fake(['api.indexnow.org/*' => Http::response('', 202)]);

        $this->artisan('seo:indexnow')->assertSuccessful();

        Http::assertSent(fn ($request) => $request['key'] === 'test-key-0123456789'
            && $request['keyLocation'] === route('indexnow.key')
            && in_array(url('/osrs-bingo-tile-ideas'), $request['urlList'], true));
    }

    #[Test]
    public function seo_indexnow_refuses_without_a_key(): void
    {
        config(['services.indexnow.key' => null]);
        Http::fake();

        $this->artisan('seo:indexnow')->assertFailed();

        Http::assertNothingSent();
    }
}
