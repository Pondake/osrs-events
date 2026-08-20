<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves editable public pages through the CMS renderer
 * (resources/js/Components/Cms/PageRenderer.vue).
 *
 * The block list was hardcoded here while the renderer was being proved
 * against a real page. It now comes from the `pages` table, which was
 * designed around the document shape the renderer had already been shown to
 * accept rather than one guessed at up front.
 *
 * The props reaching Vue are the raw stored document. That is deliberate:
 * sanitising happens in the renderer, which has to do it anyway because
 * seeders and fixtures write to this table too.
 */
class PageController extends Controller
{
    public function show(Page $page): Response
    {
        // Unpublished pages stay editable in admin but are not public. 404
        // rather than 403 — telling a stranger a hidden page exists is itself
        // information.
        abort_unless($page->is_published, 404);

        return Inertia::render('Page', [
            'seo' => [
                'title' => $page->seoTitle(),
                'description' => $page->seoDescription(),
            ],
            'header' => [
                'title' => $page->title,
                'subtitle' => $page->subtitle,
            ],
            'blocks' => $page->blocks,
        ]);
    }
}
