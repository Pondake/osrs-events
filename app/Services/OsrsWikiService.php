<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Search the Old School RuneScape Wiki for a page to hang a tile or a bingo
 * square on.
 *
 * The home page has promised "search the wiki directly to fill in icons and
 * titles automatically" since this stack was built, and nothing behind it
 * ever existed — the tile and square editors searched the local `tasks`
 * table, which ships with fourteen rows. Typing a boss name into it returned
 * nothing, which is what "the wiki search API is broken" was actually
 * describing: not a broken integration, an absent one.
 *
 * MediaWiki's own api.php, not a scraper. One request answers both halves of
 * what the picker needs — the matching pages and a thumbnail for each —
 * through `generator=search` feeding `prop=pageimages`.
 *
 * Wise Old Man has its own service (WiseOldManService) for hiscore data;
 * this is deliberately separate. They share a vendor only in the sense that
 * both are OSRS community projects.
 */
class OsrsWikiService
{
    private const ENDPOINT = 'https://oldschool.runescape.wiki/api.php';

    /**
     * Cached for a day. Wiki pages do change, but a tile's title and icon
     * are not something anyone needs to the minute, and this endpoint sits
     * behind an autocomplete that fires per keystroke — the cache is what
     * keeps a search box from becoming a load generator pointed at a
     * volunteer-run wiki.
     */
    private const CACHE_TTL = 86400;

    /**
     * Pages matching a search term, newest-first by relevance.
     *
     * Returns [] rather than throwing on failure: this feeds an autocomplete
     * beside a field somebody can still type into by hand, so an outage
     * should cost suggestions, not the ability to fill the form in.
     *
     * @return array<int, array{title: string, icon_url: ?string, url: string, page_id: int}>
     */
    public function search(string $term, int $limit = 10): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $cacheKey = 'osrs-wiki:search:'.md5(mb_strtolower($term).":{$limit}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($term, $limit) {
            try {
                $response = Http::withHeaders([
                    // The wiki asks for a descriptive agent with a contact
                    // route, and answers a generic one with 403s under load.
                    'User-Agent' => 'osrs-events (https://github.com/marthijnb/osrs-events)',
                ])
                    ->timeout(8)
                    ->get(self::ENDPOINT, [
                        'action' => 'query',
                        // generator=search feeds the search hits into
                        // prop=pageimages, so the thumbnails come back in the
                        // same round trip instead of one request per result.
                        'generator' => 'search',
                        'gsrsearch' => $term,
                        'gsrlimit' => $limit,
                        'prop' => 'pageimages|info',
                        'piprop' => 'thumbnail',
                        'pithumbsize' => 64,
                        'inprop' => 'url',
                        'format' => 'json',
                        // Without this, `pages` is an object keyed by page id
                        // and `index` ordering has to be reconstructed by
                        // hand. formatversion=2 returns a plain ordered list.
                        'formatversion' => 2,
                    ])
                    ->throw();
            } catch (\Throwable $e) {
                Log::warning("OSRS wiki search failed for \"{$term}\": {$e->getMessage()}");

                return [];
            }

            $pages = $response->json('query.pages') ?? [];

            return collect($pages)
                // `index` is MediaWiki's relevance ordering. The array comes
                // back in it already, but sorting explicitly means a future
                // formatversion change cannot silently shuffle the results.
                ->sortBy('index')
                ->map(fn (array $page) => [
                    'page_id' => (int) $page['pageid'],
                    'title' => $page['title'],
                    'icon_url' => $page['thumbnail']['source'] ?? null,
                    'url' => $page['canonicalurl'] ?? 'https://oldschool.runescape.wiki/w/'.rawurlencode(str_replace(' ', '_', $page['title'])),
                ])
                ->values()
                ->all();
        });
    }
}
