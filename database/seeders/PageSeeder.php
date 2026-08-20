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
