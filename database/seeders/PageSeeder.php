<?php

namespace Database\Seeders;

use App\Models\Page;
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

        $this->seedPage('privacy', 'Privacy Policy', 'What we collect, and what we do not.', $this->privacyBlocks());
        $this->seedPage('terms', 'Terms of Service', 'The rules for using OSRS Events.', $this->termsBlocks());
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
     * The privacy policy, moved out of a hardcoded Vue page.
     *
     * **Rewritten rather than transcribed.** The old copy said the app
     * collects no email address and no passwords, which stopped being true
     * when email registration shipped, and it predated both the OSRS username
     * and the audit log. Carrying a knowingly-false privacy statement into
     * the database would have been worse than leaving it in Vue.
     *
     * Written from what the schema actually stores. It still wants a read
     * from the owner before launch — this is a legal document, and being
     * accurate is the floor, not the whole bar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function privacyBlocks(): array
    {
        return [
            ['type' => 'prose', 'props' => ['text' => 'OSRS Events is run by one person as a free, ad-free hobby project. This page says exactly what it stores about you and why. Last updated August 2026.']],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'What we store'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Only what the site needs to work. Which of these exist depends on how you signed up:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => '**Discord ID, username and avatar** — if you log in with Discord, so we can recognise you and show you to your clan.'],
                        ['text' => '**Email address and a hashed password** — if you sign up directly instead. The password is hashed, never stored as you typed it.'],
                        ['text' => '**Your OSRS account name** — required, because skill races are scored from the public hiscores and those are looked up by name.'],
                        ['text' => '**A display name**, if you set one instead of using your Discord name.'],
                        ['text' => '**Your Discord servers**, cached from Discord when you log in, so events restricted to a server know whether you are in it.'],
                        ['text' => '**Event progress** — which events you joined, your position on a board, tiles you completed, and XP figures read from the hiscores.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'What we do not do'],
                'blocks' => [
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'We do not read your Discord messages. The login only ever asks for your identity and your server list.'],
                        ['text' => 'We do not run analytics, advertising or third-party tracking of any kind.'],
                        ['text' => 'We do not sell or share your data with anyone.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Admin actions are logged'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Actions taken by site admins — granting a role, deleting an account — are recorded in an audit log so there is a record of who did what. Those entries keep the display name of the account they were about **even after that account is deleted**, because an entry that reads "deleted user" is not a record of anything.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Other services'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Two outside services are involved, and each has its own policy:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => '**Discord**, for logging in — see [their privacy policy](https://discord.com/privacy).'],
                        ['text' => '**[Wise Old Man](https://wiseoldman.net)**, for reading OSRS hiscores. We send them the OSRS account name you gave us so they can return your XP. Nothing else about you is sent.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Your data is yours'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'You can change your display name and OSRS username yourself under Settings. Ask and your account will be deleted, along with the progress attached to it.']],
                    ['type' => 'links', 'props' => ['links' => [
                        ['label' => 'Request account deletion', 'to' => 'mailto:dev@absolit.nl', 'icon' => 'i-lucide-mail', 'variant' => 'outline'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'callout',
                'props' => [
                    'color' => 'neutral',
                    'icon' => 'i-lucide-info',
                    'title' => 'Changes to this policy',
                    'description' => 'If what we store changes, this page changes with it. It is dated at the top so you can tell when it last did.',
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function termsBlocks(): array
    {
        return [
            ['type' => 'prose', 'props' => ['text' => 'Using OSRS Events means accepting what is on this page. It is a free hobby project, and these terms are written to match that rather than to sound like a law firm. Last updated August 2026.']],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Your account'],
                'blocks' => [
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'Sign in with Discord or with an email address — either is fine.'],
                        ['text' => 'One account per person. Extra accounts made to enter an event twice will be removed.'],
                        ['text' => 'Give your real OSRS account name. Events are scored from the hiscores, so a wrong name only means you score nothing.'],
                        ['text' => 'You are responsible for what happens under your account.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Fair use'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Behave the way you would want everyone else to. Specifically, do not:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'Harass, abuse or impersonate other players.'],
                        ['text' => 'Scrape the site or hammer it with automated requests.'],
                        ['text' => 'Exploit bugs instead of reporting them. Reporting one is genuinely appreciated.'],
                        ['text' => 'Falsify event progress or claim tiles you did not complete.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'No guarantees'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'This is provided as-is. It may go down, lose data, or change without warning, and it depends on outside services — Discord for login, the OSRS hiscores through Wise Old Man for scoring — that can be unavailable on their own schedule. Accounts that break these terms can be removed.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'callout',
                'props' => [
                    'color' => 'warning',
                    'icon' => 'i-lucide-alert-triangle',
                    'title' => 'Not affiliated with Jagex',
                    'description' => 'OSRS Events is an unofficial fan project. Old School RuneScape is a trademark of Jagex Ltd, who neither endorse nor are involved with this site.',
                ],
            ],
        ];
    }

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
