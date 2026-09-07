<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `/about`, which stopped being a CMS document on 2026-09-07.
 *
 * Two things are worth pinning rather than trusted to stay true, and they are
 * the two the move was made for:
 *
 *   - the page publishes no email address. The one on /privacy is deliberate
 *     — it is the route back for somebody locked out of their account — and
 *     this page carrying a second copy was what made removing it there
 *     pointless;
 *   - a leftover `about` row cannot serve anything. Every environment that
 *     ran PageSeeder before the move still has one, holding copy that had
 *     already drifted several rewrites behind the repository.
 */
class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_from_code_rather_than_a_page_row(): void
    {
        $page = $this->get('/about')->assertOk()->viewData('page');

        $this->assertSame('About', $page['component']);
        $this->assertCount(6, $page['props']['features']);
    }

    /**
     * Asserted against the translation file rather than the response, and
     * that is not a shortcut — it is where the address would actually be.
     * Every string on this page is resolved in the browser from
     * lang/en.json, so nothing it says appears in the HTML this suite sees.
     * A response assertion would pass no matter what the page said.
     */
    #[Test]
    public function no_about_string_publishes_an_email_address(): void
    {
        $strings = collect(json_decode(file_get_contents(lang_path('en.json')), true))
            ->filter(fn ($value, $key) => str_starts_with($key, 'about.'));

        $this->assertNotEmpty($strings, 'The about.* namespace is gone, so this guards nothing.');

        foreach ($strings as $key => $value) {
            $this->assertDoesNotMatchRegularExpression(
                '/[\w.+-]+@[\w-]+\.[\w.]+|mailto:/i',
                $value,
                "{$key} publishes an email address.",
            );
        }
    }

    /**
     * The row an existing environment still has. It must not answer at
     * /about — that would put stale copy on the same URL as the component —
     * and it must not be offered in the CMS inventory as an editable page,
     * since editing it would change nothing anybody can see.
     */
    #[Test]
    public function a_leftover_about_row_is_retired_rather_than_served(): void
    {
        Page::create([
            'slug' => 'about',
            'title' => 'Stale About',
            'is_published' => true,
            'blocks' => [['type' => 'prose', 'props' => ['text' => 'Copy from before the move.']]],
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertDontSee('Copy from before the move.')
            ->assertDontSee('Stale About');

        $this->assertContains('about', Page::PARTIAL_SLUGS);
    }
}
