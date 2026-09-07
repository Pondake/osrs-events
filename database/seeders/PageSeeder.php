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
 *
 * What is left here is two legal pages and one partial. Every page that
 * started here and then moved back into a component did so because of the
 * paragraph above: a seeder that cannot reach an existing row is the wrong
 * home for copy that has to stay true.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        // /about used to be seeded here as a full CMS document. Dropped
        // 2026-09-07: it is a component now (LandingController::about()), for
        // the same reasons the guide pages below stopped being rows. Still
        // listed in Page::PARTIAL_SLUGS regardless, since an environment that
        // ran this seeder before the change still has the row and it must
        // stay out of the CMS inventory and the /{page} catch-all.
        //
        // This is also the clearest example of why firstOrCreate cuts both
        // ways. The row that shipped served copy several rewrites behind what
        // this file said, and nothing anywhere reported the difference — the
        // seeder had been correct and unread for weeks.

        // Partly editable, unlike the others: Home.vue takes its hero copy
        // and one block region from this row and keeps the rest — the
        // auth-dependent button, the feature grid, the admin section — in
        // code. Seeded with an empty block list on purpose, so the page looks
        // exactly as it did until someone actually adds something.
        $this->seedPage('home', __('home.title'), __('home.description'), []);

        // The six OSRS Events guide pages (Snakes & Ladders, Bingo, Skill
        // Race, Drop Race, Clan Events, Event Ideas) used to seed a partial
        // Page row here so an admin could edit their FAQ without a deploy.
        // Dropped: the guide pages are static content now, written directly
        // in LandingController and lang/en.json — see that controller's own
        // comment on snakesAndLadders() for why. Still listed in
        // Page::PARTIAL_SLUGS regardless, since an environment that already
        // ran this seeder before the change still has these rows and they
        // must stay excluded from the CMS inventory and the /{page} catch-all.

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
}
