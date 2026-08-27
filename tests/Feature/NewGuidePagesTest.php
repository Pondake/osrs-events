<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Bingo, Skill Race and Drop Race guides — same host/player two-track
 * shape as SnakesAndLadders, added once all three event types actually
 * shipped (Event::EVENT_TYPES) rather than leaving them undocumented on the
 * public site.
 */
class NewGuidePagesTest extends TestCase
{
    use RefreshDatabase;

    public static function guides(): array
    {
        return [
            'bingo' => ['/osrs-bingo', 'OsrsBingo'],
            'skill race' => ['/osrs-skill-race', 'OsrsSkillRace'],
            'drop race' => ['/osrs-drop-race', 'OsrsDropRace'],
        ];
    }

    #[Test]
    #[DataProvider('guides')]
    public function it_renders_with_host_and_player_steps_and_a_faq(string $path, string $component): void
    {
        $this->get($path)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component($component)
                ->has('hostSteps', 5)
                ->has('playerSteps', 4)
                ->has('modes', 3)
                ->has('faqs', 4));
    }

    #[Test]
    #[DataProvider('guides')]
    public function its_faq_reaches_the_json_ld(string $path): void
    {
        $html = $this->get($path)->getContent();

        $this->assertStringContainsString('"@type":"FAQPage"', $html);
    }
}
