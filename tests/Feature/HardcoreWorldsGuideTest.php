<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Hardcore Worlds guide. Its lists are built from numbered i18n keys,
 * so a miscounted range does not fail loudly: trans() hands back the key
 * itself and the page shows "landing.hardcore.square13" to every visitor.
 */
class HardcoreWorldsGuideTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_every_section(): void
    {
        $this->get('/osrs-hardcore-worlds-clan-events')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('OsrsHardcoreWorlds')
                ->has('facts', 5)
                ->has('formats', 4)
                ->has('deathRules', 4)
                ->has('squares', 12)
                ->has('faqs', 5));
    }

    #[Test]
    public function no_list_shows_an_untranslated_key(): void
    {
        $props = $this->get('/osrs-hardcore-worlds-clan-events')->viewData('page')['props'];

        $strings = collect($props)
            ->only(['facts', 'formats', 'deathRules', 'squares', 'faqs'])
            ->flatten();

        foreach ($strings as $string) {
            $this->assertStringNotContainsString('landing.hardcore.', (string) $string);
        }
    }

    /** A note only where one is written, not an empty line under every square. */
    #[Test]
    public function only_squares_with_a_note_carry_one(): void
    {
        $squares = $this->get('/osrs-hardcore-worlds-clan-events')->viewData('page')['props']['squares'];

        $this->assertSame(
            [trans('landing.hardcore.square8')],
            collect($squares)->whereNotNull('note')->pluck('title')->all(),
        );
    }

    #[Test]
    public function its_faq_reaches_the_json_ld(): void
    {
        $html = $this->get('/osrs-hardcore-worlds-clan-events')->getContent();

        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('Does OSRS Events track deaths?', $html);
    }
}
