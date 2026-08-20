<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves public pages as block lists for the CMS renderer
 * (resources/js/Components/Cms/PageRenderer.vue).
 *
 * The lists are hardcoded here **on purpose, for now**. Per the sequencing
 * note in docs/backlog.md, the renderer is the risky half of the CMS and the
 * one that makes storage and an editor worth building; proving it against a
 * real page first means the `pages` table can be designed around a block
 * shape that is known to render, rather than guessed at.
 *
 * Hardcoding it in the controller rather than in the Vue page is what makes
 * that proof worth anything: the blocks reach the renderer as plain JSON
 * over an Inertia prop, which is exactly how they will arrive from the
 * database. Swapping the source later is a change to this method only.
 *
 * Copy still goes through __() so nothing regresses while the content lives
 * in lang/en.json. When blocks become rows, the strings move with them.
 */
class PageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('About', [
            'seo' => [
                'title' => __('seo.about_title'),
                'description' => __('seo.about_desc'),
            ],
            'header' => [
                'title' => __('about.title'),
                'subtitle' => __('about.subtitle'),
            ],
            'blocks' => $this->aboutBlocks(),
        ]);
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
                    [
                        'type' => 'links',
                        'props' => [
                            'links' => [
                                ['label' => __('about.privacy_cta'), 'to' => '/privacy', 'icon' => 'i-lucide-arrow-right', 'variant' => 'outline'],
                            ],
                        ],
                    ],
                ],
            ],

            ['type' => 'separator', 'props' => []],

            [
                'type' => 'section',
                'props' => ['title' => __('about.free_title')],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => __('about.free_body')]],
                    [
                        'type' => 'links',
                        'props' => [
                            'links' => [
                                // Straight to Ko-fi, no page in between; the
                                // profile is admin-editable in site settings.
                                ['label' => __('about.donate_cta'), 'to' => Setting::get('kofi_url'), 'icon' => 'i-lucide-coffee', 'color' => 'warning', 'variant' => 'outline'],
                            ],
                        ],
                    ],
                ],
            ],

            ['type' => 'separator', 'props' => []],

            [
                'type' => 'section',
                'props' => ['title' => __('about.support_title')],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => __('about.support_body')]],
                    [
                        'type' => 'links',
                        'props' => [
                            'links' => [
                                ['label' => __('about.support_email'), 'to' => 'mailto:dev@absolit.nl', 'icon' => 'i-lucide-mail', 'variant' => 'outline'],
                            ],
                        ],
                    ],
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
