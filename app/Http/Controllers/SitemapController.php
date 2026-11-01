<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

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
     * `lastmod` is the date the page's content last changed, kept by hand:
     * Google only trusts it while it stays accurate, and a file mtime would
     * move on every deploy. Bump it when a page's copy changes.
     *
     * @var array<string, array{priority: string, changefreq: string, lastmod?: string}>
     */
    private const STATIC_PATHS = [
        '/' => ['priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => '2026-11-01'],
        '/events' => ['priority' => '0.8', 'changefreq' => 'daily'],
        '/osrs-snakes-and-ladders' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-11-01'],
        '/osrs-clan-events' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-08-27'],
        '/osrs-event-ideas' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-10-21'],
        '/osrs-bingo' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-10-21'],
        '/osrs-bingo-tile-ideas' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-10-21'],
        '/osrs-skill-race' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-09-24'],
        '/osrs-drop-race' => ['priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-09-24'],
        // Listed here rather than picked up as a CMS row: /about stopped
        // being one on 2026-09-07. Lower priority than the guides, which are
        // what the search traffic is actually for.
        '/about' => ['priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => '2026-09-23'],
        '/supporters' => ['priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => '2026-09-23'],
    ];

    public function __invoke(): Response
    {
        $xml = view('sitemap', ['urls' => $this->entries()])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Every URL the sitemap lists. Public so `seo:indexnow` submits exactly
     * the same set.
     *
     * @return array<int, array{loc: string, priority: string, changefreq: string, lastmod: ?string}>
     */
    public function entries(): array
    {
        $urls = [];

        // Behind the lock /events only redirects to the door.
        $locked = Setting::get('site_lock_enabled') || Setting::get('admin_lockdown_enabled');

        foreach (self::STATIC_PATHS as $path => $meta) {
            if ($locked && $path === '/events') {
                continue;
            }

            $urls[] = [
                'loc' => $path === '/' ? rtrim(url('/'), '/').'/' : url($path),
                'priority' => $meta['priority'],
                'changefreq' => $meta['changefreq'],
                'lastmod' => $path === '/events' ? $this->eventsLastmod() : ($meta['lastmod'] ?? null),
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

        return $urls;
    }

    private function eventsLastmod(): ?string
    {
        $latest = Event::where('is_listed', true)->max('updated_at');

        return $latest === null ? null : Carbon::parse($latest)->toAtomString();
    }
}
