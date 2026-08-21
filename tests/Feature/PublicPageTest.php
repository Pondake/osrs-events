<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The editable public pages.
 *
 * These sit on a catch-all `/{page}` at the very bottom of the route file,
 * which makes two things worth pinning: that the rows which are not meant to
 * be pages of their own stay unreachable, and that the catch-all has not
 * quietly started shadowing a real route.
 */
class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'about',
            'title' => 'About us',
            'subtitle' => 'Who runs this',
            'is_published' => true,
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'We run clan events.'],
            ],
        ], $attributes));
    }

    #[Test]
    public function a_published_page_renders_its_blocks(): void
    {
        $this->page();

        $props = $this->get('/about')->viewData('page')['props'];

        $this->assertSame('About us', $props['header']['title']);
        $this->assertSame('Who runs this', $props['header']['subtitle']);
        $this->assertSame('We run clan events.', $props['blocks'][0]['text']);
    }

    #[Test]
    public function it_carries_its_own_seo_title_and_description(): void
    {
        $this->page();

        $seo = $this->get('/about')->viewData('page')['props']['seo'];

        $this->assertNotEmpty($seo['title']);
        $this->assertNotEmpty($seo['description']);
    }

    /**
     * 404 rather than 403: telling a stranger that a hidden page exists is
     * itself something they did not have.
     */
    #[Test]
    public function an_unpublished_page_is_not_merely_forbidden_but_absent(): void
    {
        $this->page(['is_published' => false]);

        $this->get('/about')->assertNotFound();
    }

    /**
     * A partial's row exists to feed a hand-written component, not to be a
     * page of its own — serving it here would put the same copy on a second
     * URL with none of the parts that component adds.
     */
    #[Test]
    public function a_partial_is_not_reachable_as_a_page(): void
    {
        $slug = Page::PARTIAL_SLUGS[0];

        $this->page(['slug' => $slug, 'is_published' => true]);

        $this->get("/{$slug}")->assertNotFound();
    }

    #[Test]
    public function an_unknown_slug_is_a_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    /**
     * The catch-all is last in the route file, so a row whose slug matches a
     * real route must not be what answers that URL. A page called "events"
     * shadowing the events list would be a silent, total swap.
     */
    #[Test]
    public function a_page_cannot_shadow_a_real_route(): void
    {
        $this->page(['slug' => 'events', 'title' => 'Not the events list']);

        $this->get('/events')
            ->assertOk()
            ->assertDontSee('Not the events list', escape: false);
    }
}
