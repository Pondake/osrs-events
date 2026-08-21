<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * The home page is **partly** editable, unlike /about, /privacy and
     * /terms which are wholly CMS documents.
     *
     * The copy an admin should be able to change — the hero headline and
     * standfirst, plus a free content region — comes from a `pages` row.
     * Everything else stays in the component because it is behaviour, not
     * text: the hero button depends on whether you are signed in, the admin
     * section only exists for admins, and the feature grid and guide links
     * are structured data the CMS vocabulary has no equivalent for.
     *
     * `firstWhere` rather than a required lookup: the page renders from its
     * own translations if the row is missing, so a fresh install without the
     * seeder is a plain home page, not a 500.
     */
    public function home(): Response
    {
        $page = Page::where('slug', 'home')->where('is_published', true)->first();

        return Inertia::render('Home', [
            'page' => $page === null ? null : [
                'title' => $page->title,
                'subtitle' => $page->subtitle,
                'blocks' => $page->blocks ?? [],
            ],
        ]);
    }

    /**
     * SEO-heaviest of the three Nuxt landing pages — the one the prototype
     * was scoped to, since it carries both FAQPage and HowTo JSON-LD plus a
     * full meta/canonical/OG set. Mirrors the shape of
     * frontend/app/pages/osrs-snakes-and-ladders.vue's <script setup>: the
     * server builds the same faqs/steps/sizes arrays and passes them as
     * props, so the Vue page can build identical JSON-LD from identical data
     * rather than duplicating the copy in two places.
     */
    public function snakesAndLadders(): Response
    {
        $steps = [
            ['icon' => 'i-lucide-layout-grid', 'title' => 'Create a board', 'description' => 'Pick a board size and generate a fresh Snakes & Ladders grid for your clan.'],
            ['icon' => 'i-lucide-list-checks', 'title' => 'Set the tiles', 'description' => 'Assign an OSRS task, boss kill or drop to every tile on the board.'],
            ['icon' => 'i-lucide-arrow-up-from-line', 'title' => 'Invite your team', 'description' => 'Share the board link or a Discord invite so teammates can join.'],
            ['icon' => 'i-simple-icons-discord', 'title' => 'Sync with Discord', 'description' => 'Members log in with Discord so progress is tied to a real identity.'],
            ['icon' => 'i-lucide-dice-6', 'title' => 'Roll and climb', 'description' => 'Complete a tile to roll and move up the board — or land on a snake.'],
        ];

        $sizes = [
            ['icon' => 'i-lucide-grid-3x3', 'title' => '5x5 board', 'description' => 'A quick 25-tile board for a short event or a small clan.'],
            ['icon' => 'i-lucide-grid-3x3', 'title' => '7x7 board', 'description' => 'A 49-tile board with room for a longer running event.'],
            ['icon' => 'i-lucide-grid-3x3', 'title' => '9x9 board', 'description' => 'An 81-tile marathon board for large, long-running clan events.'],
        ];

        $faqs = [
            ['question' => 'What is an OSRS Snakes and Ladders board?', 'answer' => 'A clan event board inspired by the classic board game — tiles are OSRS tasks instead of squares, and snakes send you back down the board.'],
            ['question' => 'Do I need Discord to play?', 'answer' => 'You need a Discord account to log in, since board membership and progress are tied to your Discord identity.'],
            ['question' => 'Can I customise the tiles?', 'answer' => 'Yes — board owners can set a custom task, boss or drop requirement for every tile.'],
            ['question' => 'How many players can join a board?', 'answer' => 'As many as your clan wants; boards support teams as well as individual players.'],
            ['question' => 'Is it free to use?', 'answer' => 'Yes, OSRS Events is free for any clan to use.'],
            ['question' => 'Can I run multiple boards at once?', 'answer' => 'Yes, you can create and manage several boards for different events at the same time.'],
            ['question' => 'What happens when I land on a snake?', 'answer' => 'You slide back down to the tile at the bottom of that snake, same as the classic board game.'],
        ];

        // JSON-LD is deliberately NOT passed through Inertia's <Head> component
        // (see resources/js/Pages/SnakesAndLadders.vue's old v-html attempt).
        // Inertia 2.x's Head SSR serializer emits v-html content on raw-text
        // elements (<script>, <style>) as a literal `innerHTML="..."` HTML
        // ATTRIBUTE rather than the tag's text content — invisible to any
        // crawler reading pre-hydration HTML, which is the entire point of
        // JSON-LD.
        //
        // It's also deliberately pre-encoded to a JSON STRING here rather
        // than shared as a PHP array for app.blade.php to json_encode()
        // itself. Blade compiles directives via a text-level regex over the
        // ENTIRE template — including inside {!! !!} raw-PHP blocks — with no
        // awareness of PHP string-literal context. Laravel 11+ registers an
        // `@context` directive (for the context() logging helper), which
        // collides byte-for-byte with the '@context' key every JSON-LD block
        // requires: writing ['@context' => ...] directly in the .blade.php
        // file gets silently rewritten into that directive's compiled PHP
        // instead of staying a string. Confirmed by curling this route's SSR
        // output. Building the JSON entirely in the controller sidesteps it.
        View::share('jsonLd', array_map(
            fn ($block) => json_encode(['@context' => 'https://schema.org', ...$block]),
            [
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($faq) => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                    ], $faqs),
                ],
                [
                    '@type' => 'HowTo',
                    'name' => 'How it works',
                    'description' => 'Five steps from empty board to a running clan event.',
                    'step' => array_map(fn ($step, $i) => [
                        '@type' => 'HowToStep',
                        'position' => $i + 1,
                        'name' => $step['title'],
                        'text' => $step['description'],
                    ], $steps, array_keys($steps)),
                ],
            ],
        ));

        return Inertia::render('SnakesAndLadders', [
            'steps' => $steps,
            'sizes' => $sizes,
            'faqs' => $faqs,
        ]);
    }

    public function clanEvents(): Response
    {
        $faqs = [
            ['question' => 'Do I need to be a clan leader to run an event?', 'answer' => 'No. Anyone can create a board. If you lock it to a Discord server, players just need to be members of that server to join.'],
            ['question' => 'Can we run more than one event at once?', 'answer' => 'Yes. There is no limit on boards, and each one has its own access rules, teams and leaderboard.'],
            ['question' => 'Can two clans compete against each other?', 'answer' => 'Yes. Use a team board with one team per clan, or an open board that both rosters can join.'],
            ['question' => 'What happens when an event ends?', 'answer' => 'The board stays available to read after its end date, so you keep the final standings and can look back at previous events.'],
            ['question' => 'Is any of this paid?', 'answer' => 'No. Every feature is free, with no ads and no paid tier. Donations cover hosting.'],
        ];

        $this->shareFaqJsonLd($faqs);

        return Inertia::render('OsrsClanEvents', ['faqs' => $faqs]);
    }

    public function eventIdeas(): Response
    {
        // The page is a ranked rundown of formats, so ItemList is the type
        // that actually describes it — FAQPage (what the other two use)
        // would be a lie here. Titles are duplicated from the Vue page's
        // trans() calls rather than shared: JSON-LD has to be emitted
        // server-side (see the note in snakesAndLadders() on why Inertia's
        // <Head> can't carry it), and the page's copy lives in the JS
        // translation file. Keep the two in step by hand.
        $formats = [
            'Snakes & Ladders', 'Bingo', 'Drop log race', 'Skill race',
            'Speedrun ladder', 'Achievement diary or quest race', 'Battleship',
            'Collection log push',
        ];

        View::share('jsonLd', [json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'OSRS clan event formats',
            'description' => 'Eight event formats for Old School RuneScape clans, compared.',
            'numberOfItems' => count($formats),
            'itemListElement' => array_map(fn ($name, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $name,
            ], $formats, array_keys($formats)),
        ])]);

        return Inertia::render('OsrsEventIdeas');
    }

    /** @param array<int, array{question: string, answer: string}> $faqs */
    private function shareFaqJsonLd(array $faqs): void
    {
        View::share('jsonLd', [json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ])]);
    }
}
