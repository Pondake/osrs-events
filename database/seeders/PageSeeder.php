<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\LegalPages;
use Illuminate\Database\Seeder;

/**
 * Seeds the pages that used to be hardcoded.
 *
 * `firstOrCreate` on the slug, not `updateOrCreate`: once a page is in the
 * table it is editable content, and a re-run must not overwrite whatever an
 * admin has since changed. Adding a NEW page here still works; editing an
 * existing one is the editor's job, not the seeder's.
 *
 * Copy still comes from __() so the initial content matches what the page
 * said before it moved into the database. From here on the strings live in
 * the `blocks` column and lang/en.json is no longer their source.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => __('about.title'),
                'subtitle' => __('about.subtitle'),
                'seo_title' => __('seo.about_title'),
                'seo_description' => __('seo.about_desc'),
                'is_published' => true,
                'blocks' => $this->aboutBlocks(),
            ],
        );

        $this->command?->info($page->wasRecentlyCreated
            ? 'Seeded the About page.'
            : 'About page already exists — left untouched.');

        // Partly editable, unlike the others: Home.vue takes its hero copy
        // and one block region from this row and keeps the rest — the
        // auth-dependent button, the feature grid, the admin section — in
        // code. Seeded with an empty block list on purpose, so the page looks
        // exactly as it did until someone actually adds something.
        $this->seedPage('home', __('home.title'), __('home.description'), []);

        // The three SEO landing pages, also partial. Only their FAQ is
        // editable — the step and format lists stay in code because they
        // drive HowTo and ItemList schema the block vocabulary cannot
        // express. The FAQ is both the visible copy AND the FAQPage
        // structured data, which is exactly why it must have one source.
        $this->seedPage(
            'osrs-snakes-and-ladders',
            'OSRS Snakes & Ladders',
            'How a Snakes & Ladders clan event works.',
            [['type' => 'faq', 'props' => ['items' => [
                ['question' => 'What is an OSRS Snakes and Ladders board?', 'answer' => 'A clan event board inspired by the classic board game — tiles are OSRS tasks instead of squares, and snakes send you back down the board.'],
                ['question' => 'Do I need Discord to play?', 'answer' => 'No. Discord login is the quickest way in, but you can sign up with an email address instead.'],
                ['question' => 'Can I customise the tiles?', 'answer' => 'Yes — board owners can set a custom task, boss or drop requirement for every tile.'],
                ['question' => 'How many players can join a board?', 'answer' => 'As many as your clan wants; boards support teams as well as individual players.'],
                ['question' => 'Is it free to use?', 'answer' => 'Yes, OSRS Events is free for any clan to use.'],
                ['question' => 'Can I run multiple boards at once?', 'answer' => 'Yes, you can create and manage several boards for different events at the same time.'],
                ['question' => 'What happens when I land on a snake?', 'answer' => 'You slide back down to the tile at the bottom of that snake, same as the classic board game.'],
            ]]]],
        );

        $this->seedPage(
            'osrs-clan-events',
            'OSRS Clan Events',
            'Running events for an Old School RuneScape clan.',
            [['type' => 'faq', 'props' => ['items' => [
                ['question' => 'Do I need to be a clan leader to run an event?', 'answer' => 'No. Anyone can create an event. If you lock it to a Discord server, players just need to be members of that server to join.'],
                ['question' => 'Can we run more than one event at once?', 'answer' => 'Yes. There is no limit, and each event has its own access rules, teams and leaderboard.'],
                ['question' => 'Can two clans compete against each other?', 'answer' => 'Yes. Use a team board with one team per clan, or an open event that both rosters can join.'],
                ['question' => 'What happens when an event ends?', 'answer' => 'It stays available to read after its end date, so you keep the final standings and can look back at previous events.'],
                ['question' => 'Is any of this paid?', 'answer' => 'No. Every feature is free, with no ads and no paid tier. Donations cover hosting.'],
            ]]]],
        );

        $this->seedPage(
            'osrs-event-ideas',
            'OSRS Event Ideas',
            'Event formats for Old School RuneScape clans.',
            [],
        );

        $this->seedPage('privacy', 'Privacy Policy', 'What we collect, and what we do not.', LegalPages::privacy());
        $this->seedPage('terms', 'Terms of Service', 'The rules for using OSRS Events.', LegalPages::terms());
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function seedPage(string $slug, string $title, string $subtitle, array $blocks): void
    {
        $page = Page::firstOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'subtitle' => $subtitle,
                // No site-name suffix: the Inertia title callback appends
                // one already, and setting it here too produced
                // "Privacy Policy — OSRS Events - OSRS Events". That is SSR
                // gotcha #4 in the backlog — the suffix lives in exactly one
                // place.
                'seo_title' => $title,
                'seo_description' => $subtitle,
                'is_published' => true,
                'blocks' => $blocks,
            ],
        );

        $this->command?->info($page->wasRecentlyCreated
            ? "Seeded the {$title} page."
            : "{$title} page already exists — left untouched.");
    }

    /**
     * The privacy policy and the terms live in App\Support\LegalPages, not
     * here.
     *
     * They were inline until 2026-08-24. The problem was not size, it was
     * reach: `seedPage` uses firstOrCreate, so on any environment that had
     * already run, editing the text here changed nothing and said nothing —
     * and these are the two pages that must match what the code does. Moving
     * them out gave `pages:sync-legal` something to apply to a database whose
     * rows already exist.
     */

    /** @return array<int, array<string, mixed>> */
    private function aboutBlocks(): array
    {
        return [
            [
                'type' => 'section',
                'props' => ['title' => __('about.offer_title')],
                'blocks' => [
                    [
                        'type' => 'features',
                        'props' => [
                            'columns' => 3,
                            'items' => [
                                ['icon' => 'i-lucide-grid-3x3', 'title' => __('about.feature_boards_title'), 'description' => __('about.feature_boards_desc')],
                                ['icon' => 'i-lucide-dice-6', 'title' => __('about.feature_dice_title'), 'description' => __('about.feature_dice_desc')],
                                ['icon' => 'i-lucide-check-square', 'title' => __('about.feature_tasks_title'), 'description' => __('about.feature_tasks_desc')],
                                ['icon' => 'i-lucide-message-circle', 'title' => __('about.feature_discord_title'), 'description' => __('about.feature_discord_desc')],
                                ['icon' => 'i-lucide-moon', 'title' => __('about.feature_dark_title'), 'description' => __('about.feature_dark_desc')],
                                ['icon' => 'i-lucide-heart', 'title' => __('about.feature_free_title'), 'description' => __('about.feature_free_desc')],
                            ],
                        ],
                    ],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => __('about.privacy_title')],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => __('about.privacy_body')]],
                    ['type' => 'links', 'props' => ['links' => [
                        ['label' => __('about.privacy_cta'), 'to' => '/privacy', 'icon' => 'i-lucide-arrow-right', 'variant' => 'outline'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => __('about.free_title')],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => __('about.free_body')]],
                    ['type' => 'links', 'props' => ['links' => [
                        // Left as a literal rather than Setting::get(): once
                        // this is editable content, an admin changing the
                        // Ko-fi setting would no longer update a link already
                        // written into a page. Better that the seeded default
                        // is honest about being a starting point.
                        ['label' => __('about.donate_cta'), 'to' => 'https://ko-fi.com/pondake', 'icon' => 'i-lucide-coffee', 'color' => 'warning', 'variant' => 'outline'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => __('about.support_title')],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => __('about.support_body')]],
                    ['type' => 'links', 'props' => ['links' => [
                        ['label' => __('about.support_email'), 'to' => 'mailto:dev@absolit.nl', 'icon' => 'i-lucide-mail', 'variant' => 'outline'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'callout',
                'props' => [
                    'color' => 'warning',
                    'icon' => 'i-lucide-alert-triangle',
                    'title' => __('about.disclaimer_title'),
                    'description' => __('about.disclaimer_body'),
                ],
            ],
        ];
    }
}
