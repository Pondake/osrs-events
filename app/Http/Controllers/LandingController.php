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
     * server builds the same faqs/hostSteps/playerSteps/sizes arrays and
     * passes them as props, so the Vue page can build identical JSON-LD from
     * identical data rather than duplicating the copy in two places.
     */
    public function snakesAndLadders(): Response
    {
        // Two tracks — what a host does to set a board up, and what a player
        // actually experiences opening one — rather than one hardcoded
        // English list. Both are real i18n keys now; the five-step version
        // this replaced was never translated at all (`'Create a board'` etc.
        // baked straight into this controller), which is exactly the
        // hardcoded-string rule CLAUDE.md's i18n section exists to catch.
        $hostSteps = collect(range(1, 5))->map(fn ($i) => [
            'title' => trans("landing.snakes.host_step{$i}_title"),
            'description' => trans("landing.snakes.host_step{$i}_desc"),
        ])->all();

        $playerSteps = collect(range(1, 4))->map(fn ($i) => [
            'title' => trans("landing.snakes.player_step{$i}_title"),
            'description' => trans("landing.snakes.player_step{$i}_desc"),
        ])->all();

        // Same fix as hostSteps/playerSteps above: this was hardcoded English
        // ('5x5 board', ...) with a matching set of `landing.snakes.size_*`
        // keys sitting in lang/en.json completely unused — the copy already
        // existed, translated, and nothing pointed at it.
        $sizes = collect(['5', '7', '9'])->map(fn ($n) => [
            'icon' => 'i-lucide-grid-3x3',
            'title' => trans("landing.snakes.size_{$n}_title"),
            'description' => trans("landing.snakes.size_{$n}_desc"),
        ])->all();

        // Static on purpose, not CMS-editable — the guide pages used to read
        // their FAQ from a `pages` row (Page::faqItems()) so an admin could
        // edit it without a deploy. Dropped in the same pass that rebuilt
        // these pages away from `u-page-section`'s landing-page spacing: the
        // CMS abstraction was solving an editing-convenience problem while
        // the actual problem was the layout, and it's easier to redesign a
        // page you can just read top to bottom in one file. Revisit CMS
        // editability later if it's still wanted once the layout is settled.
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
                    'description' => 'From an empty board to a clan actually playing it, host side and player side.',
                    // Host steps then player steps, in the order someone
                    // would actually hit them end to end — a search engine
                    // reading this as one sequence still gets the real flow,
                    // even though the page itself renders them as two tracks.
                    'step' => array_map(fn ($step, $i) => [
                        '@type' => 'HowToStep',
                        'position' => $i + 1,
                        'name' => $step['title'],
                        'text' => $step['description'],
                    ], [...$hostSteps, ...$playerSteps], array_keys([...$hostSteps, ...$playerSteps])),
                ],
            ],
        ));

        return Inertia::render('SnakesAndLadders', [
            'hostSteps' => $hostSteps,
            'playerSteps' => $playerSteps,
            'sizes' => $sizes,
            'faqs' => $faqs,
        ]);
    }

    /**
     * Same host/player two-track shape as snakesAndLadders(), one method per
     * new event type rather than a single parameterised action: each type's
     * steps, FAQ and "how progress is tracked" copy are genuinely different
     * prose, not a templated substitution, so a shared method would just be
     * passing three near-identical arrays through one signature for no
     * reduction in duplication.
     */
    public function bingo(): Response
    {
        $hostSteps = collect(range(1, 5))->map(fn ($i) => [
            'title' => trans("landing.bingo.host_step{$i}_title"),
            'description' => trans("landing.bingo.host_step{$i}_desc"),
        ])->all();

        $playerSteps = collect(range(1, 4))->map(fn ($i) => [
            'title' => trans("landing.bingo.player_step{$i}_title"),
            'description' => trans("landing.bingo.player_step{$i}_desc"),
        ])->all();

        $modes = [
            ['icon' => 'i-lucide-rows-3', 'title' => trans('landing.bingo.modes_lines_title'), 'description' => trans('landing.bingo.modes_lines_desc')],
            ['icon' => 'i-lucide-grid-3x3', 'title' => trans('landing.bingo.modes_full_title'), 'description' => trans('landing.bingo.modes_full_desc')],
            ['icon' => 'i-lucide-shield-check', 'title' => trans('landing.bingo.modes_wildcard_title'), 'description' => trans('landing.bingo.modes_wildcard_desc')],
        ];

        $faqs = collect(range(1, 4))->map(fn ($i) => [
            'question' => trans("landing.bingo.faq_q{$i}"),
            'answer' => trans("landing.bingo.faq_a{$i}"),
        ])->all();

        $this->shareFaqJsonLd($faqs);

        return Inertia::render('OsrsBingo', [
            'hostSteps' => $hostSteps,
            'playerSteps' => $playerSteps,
            'modes' => $modes,
            'faqs' => $faqs,
        ]);
    }

    /** @return Response */
    private function metricRacePage(string $key, string $component): Response
    {
        $hostSteps = collect(range(1, 5))->map(fn ($i) => [
            'title' => trans("landing.{$key}.host_step{$i}_title"),
            'description' => trans("landing.{$key}.host_step{$i}_desc"),
        ])->all();

        $playerSteps = collect(range(1, 4))->map(fn ($i) => [
            'title' => trans("landing.{$key}.player_step{$i}_title"),
            'description' => trans("landing.{$key}.player_step{$i}_desc"),
        ])->all();

        $modes = [
            ['icon' => 'i-lucide-refresh-cw', 'title' => trans("landing.{$key}.modes_auto_title"), 'description' => trans("landing.{$key}.modes_auto_desc")],
            ['icon' => 'i-lucide-zap', 'title' => trans("landing.{$key}.modes_manual_title"), 'description' => trans("landing.{$key}.modes_manual_desc")],
            ['icon' => 'i-lucide-lock', 'title' => trans("landing.{$key}.modes_locked_title"), 'description' => trans("landing.{$key}.modes_locked_desc")],
        ];

        $faqs = collect(range(1, 4))->map(fn ($i) => [
            'question' => trans("landing.{$key}.faq_q{$i}"),
            'answer' => trans("landing.{$key}.faq_a{$i}"),
        ])->all();

        $this->shareFaqJsonLd($faqs);

        return Inertia::render($component, [
            'hostSteps' => $hostSteps,
            'playerSteps' => $playerSteps,
            'modes' => $modes,
            'faqs' => $faqs,
        ]);
    }

    /**
     * SKILL_RACE and DROP_RACE share one pipeline server-side already
     * (Event::needsMetric(), EventParticipationService, the same
     * SkillRaceController routes) — only the metric vocabulary differs
     * (Event::SKILL_METRICS vs BOSS_METRICS). The guide copy mirrors that:
     * one shared builder, two lang namespaces, two Vue pages so each keeps
     * its own SEO identity and URL.
     */
    public function skillRace(): Response
    {
        return $this->metricRacePage('skill_race', 'OsrsSkillRace');
    }

    public function dropRace(): Response
    {
        return $this->metricRacePage('drop_race', 'OsrsDropRace');
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
            'Snakes & Ladders', 'Bingo', 'Drop race', 'Skill race',
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
