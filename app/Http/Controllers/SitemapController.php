<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

/**
 * The XML sitemap, built from the routes and CMS rows rather than kept as a
 * static file somebody has to remember to edit.
 *
 * There wasn't one at all — /sitemap.xml 404'd in production — which for a
 * site whose whole traffic plan is organic search is the cheapest omission
 * to fix. It is not a ranking factor in itself; it is how a new page gets
 * discovered in days instead of whenever a crawler happens across a link.
 *
 * **Only publicly reachable, indexable URLs belong here.** Everything behind
 * auth is excluded: an event page needs a login, so listing it would send
 * crawlers to a redirect and put URLs in the index that no visitor can open.
 * BoardShow and Boards/Mine already carry `noindex` for the same reason.
 */
class SitemapController extends Controller
{
    /**
     * Hand-written routes that are public. Kept as a list rather than read
     * from the route table: most named routes are private, and an allowlist
     * fails closed where a filter would quietly start publishing whatever
     * gets added next.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private const STATIC_PATHS = [
        '/' => ['priority' => '1.0', 'changefreq' => 'weekly'],
        '/events' => ['priority' => '0.8', 'changefreq' => 'daily'],
        '/osrs-snakes-and-ladders' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        '/osrs-clan-events' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        '/osrs-event-ideas' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        '/osrs-bingo' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        '/osrs-skill-race' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        '/osrs-drop-race' => ['priority' => '0.9', 'changefreq' => 'monthly'],
    ];

    public function __invoke(): Response
    {
        $urls = [];

        foreach (self::STATIC_PATHS as $path => $meta) {
            $urls[] = [
                'loc' => url($path),
                'priority' => $meta['priority'],
                'changefreq' => $meta['changefreq'],
                'lastmod' => null,
            ];
        }

        // CMS pages, minus the partial ones — those back a hand-written page
        // that is already listed above, and /home 404s by design.
        $pages = Page::query()
            ->where('is_published', true)
            ->whereNotIn('slug', Page::PARTIAL_SLUGS)
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        foreach ($pages as $page) {
            $urls[] = [
                'loc' => url('/'.$page->slug),
                'priority' => '0.5',
                'changefreq' => 'monthly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
