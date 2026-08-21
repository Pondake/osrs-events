<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The landing pages' FAQ is editable content AND their FAQPage structured
 * data. These lock the two together: schema that disagrees with the visible
 * page is worse than no schema, and the only way to guarantee they agree is
 * for both to read the same row.
 */
class LandingPageFaqTest extends TestCase
{
    use RefreshDatabase;

    private function faqPage(string $slug, array $items, bool $published = true): Page
    {
        return Page::create([
            'slug' => $slug,
            'title' => 'A landing page',
            'is_published' => $published,
            'blocks' => [['type' => 'faq', 'props' => ['items' => $items]]],
        ]);
    }

    #[Test]
    public function the_rendered_faq_comes_from_the_page_row(): void
    {
        $this->faqPage('osrs-snakes-and-ladders', [
            ['question' => 'Edited question?', 'answer' => 'Edited answer.'],
        ]);

        $this->get('/osrs-snakes-and-ladders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('faqs', 1)
                ->where('faqs.0.question', 'Edited question?'));
    }

    #[Test]
    public function the_structured_data_is_built_from_the_same_row(): void
    {
        $this->faqPage('osrs-clan-events', [
            ['question' => 'Only question?', 'answer' => 'Only answer.'],
        ]);

        $html = $this->get('/osrs-clan-events')->getContent();

        // The JSON-LD is emitted server-side into the Blade shell, so it is
        // in the served HTML rather than anything the client assembles.
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('Only question?', $html);
        $this->assertStringContainsString('Only answer.', $html);
    }

    /** Without a row the page keeps its built-in copy rather than emptying. */
    #[Test]
    public function a_missing_row_falls_back_to_the_shipped_faq(): void
    {
        $this->get('/osrs-snakes-and-ladders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('faqs', 7));
    }

    #[Test]
    public function an_unpublished_row_falls_back_too(): void
    {
        $this->faqPage('osrs-snakes-and-ladders', [
            ['question' => 'Draft?', 'answer' => 'Draft.'],
        ], published: false);

        $this->get('/osrs-snakes-and-ladders')
            ->assertInertia(fn ($page) => $page->has('faqs', 7));
    }

    /**
     * A half-filled entry is not a valid Question node, and Google rejects
     * the whole block rather than the one entry — so it is dropped here.
     */
    #[Test]
    public function an_entry_missing_a_question_or_answer_is_dropped(): void
    {
        $this->faqPage('osrs-clan-events', [
            ['question' => 'Complete?', 'answer' => 'Yes.'],
            ['question' => 'No answer?', 'answer' => ''],
            ['question' => '', 'answer' => 'No question.'],
        ]);

        $this->get('/osrs-clan-events')
            ->assertInertia(fn ($page) => $page->has('faqs', 1));
    }

    /** Nested inside a section, the entries must still be found. */
    #[Test]
    public function faq_entries_nested_in_a_section_still_reach_the_schema(): void
    {
        Page::create([
            'slug' => 'osrs-clan-events',
            'title' => 'Nested',
            'is_published' => true,
            'blocks' => [[
                'type' => 'section',
                'props' => ['title' => 'Questions'],
                'blocks' => [['type' => 'faq', 'props' => ['items' => [
                    ['question' => 'Buried?', 'answer' => 'Still found.'],
                ]]]],
            ]],
        ]);

        $this->get('/osrs-clan-events')
            ->assertInertia(fn ($page) => $page->where('faqs.0.question', 'Buried?'));
    }

    /** The row backs a component; it must not also be a page of its own. */
    #[Test]
    public function the_landing_rows_are_not_served_by_the_catch_all(): void
    {
        $this->faqPage('osrs-event-ideas', []);

        // The real landing route still works...
        $this->get('/osrs-event-ideas')->assertOk();
        // ...and there is no second copy anywhere else.
        $this->assertContains('osrs-event-ideas', Page::PARTIAL_SLUGS);
    }
}
