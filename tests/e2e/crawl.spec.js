import { test, expect } from './fixtures.js';
import { crawl } from './support/crawl.js';
import { SEATS } from './support/seats.js';

/**
 * Clicks around the site as every seat and reports what a person would run
 * into: a link that ends in an error page, a page that throws in the console,
 * a panel whose own requests come back 403.
 *
 * Which links exist is the app's own answer to "what may this seat do", so
 * this checks the menu against the routes without either being written down
 * twice. A seat that is offered a page it cannot open shows up here.
 */
const LIMIT = 70;

/**
 * Dead links that exist today, so the suite stays usable while they are
 * open. Not a place to hide things: the assertion is equality, so fixing one
 * fails the test until it is removed from here.
 */
const KNOWN_BROKEN = {
    admin: [
        '404 /admin/content/osrs-snakes-and-ladders (linked from /admin/content)',
        '404 /admin/content/osrs-clan-events (linked from /admin/content)',
        '404 /admin/content/osrs-event-ideas (linked from /admin/content)',
    ],
};

test('anonymous visitors are never offered a dead end', async ({ page, watcher }) => {
    watcher.documentsChecked();

    const { visited, broken } = await crawl(page, { limit: LIMIT });

    test.info().annotations.push({ type: 'visited', description: visited.join(' ') });

    expect(broken).toEqual([]);
    expect(visited.length).toBeGreaterThan(10);
});

for (const seat of [...Object.keys(SEATS), 'newcomer', 'unreachable']) {
    test(`${seat} is never offered a dead end`, async ({ as }) => {
        const page = await as(seat);

        page.watcher.documentsChecked();

        const { visited, broken } = await crawl(page, { limit: LIMIT });

        test.info().annotations.push({ type: 'visited', description: visited.join(' ') });

        expect(broken).toEqual(KNOWN_BROKEN[seat] ?? []);
        expect(visited.length).toBeGreaterThan(10);
    });
}
