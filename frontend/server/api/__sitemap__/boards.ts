import type { SitemapUrlInput } from '#sitemap/types';

/**
 * Dynamic sitemap entries for public board pages.
 *
 * The `boards` query is already filtered to `isListed: true` server-side, so the
 * only extra gate here is access mode: GUILD and INVITE boards are private clan
 * events and must not be advertised to search engines. Their pages stay
 * reachable — they are simply not indexed (see `useSeo` on the board page).
 *
 * A failure here must not fail the whole sitemap, so errors degrade to an empty
 * list and leave the static routes intact.
 */
export default defineSitemapEventHandler(async (): Promise<SitemapUrlInput[]> => {
  const { graphqlUrl } = useRuntimeConfig().public;

  try {
    const result = await $fetch<{
      data?: { boards?: Array<{ id: string; accessMode?: string | null; updatedAt?: string }> };
    }>(graphqlUrl, {
      method: 'POST',
      body: {
        query: 'query SitemapBoards { boards { id accessMode updatedAt } }',
      },
    });

    return (result.data?.boards ?? [])
      .filter(board => board.accessMode === 'OPEN')
      .flatMap(board => [
        {
          loc: `/boards/${board.id}`,
          lastmod: board.updatedAt,
          changefreq: 'daily' as const,
          priority: 0.7,
        },
        {
          loc: `/boards/${board.id}/leaderboard`,
          lastmod: board.updatedAt,
          changefreq: 'daily' as const,
          priority: 0.5,
        },
      ]);
  } catch (error) {
    console.error('[sitemap] failed to load boards', error);
    return [];
  }
});
