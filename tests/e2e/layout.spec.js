import { test, expect, hydrated } from './fixtures.js';
import { eventId } from './support/db.js';
import { WIDTHS } from './support/seats.js';

/**
 * Every kind of page at every width of the walkthrough, reading the numbers
 * that layout bugs show up in — not judging by eye.
 *
 * 1280, 1024, 768 and 375: the band between tablet and desktop is where the
 * bugs are, and it is the one that gets skipped. The width the browser
 * actually gave is asserted first; a sweep that believes it is at a width it
 * is not proves nothing about that width.
 */
const EVENT_TITLES = [
    'E2E Ladder',
    'The Grand Midsummer Clan Championship of Old School RuneScape — Season Four',
    'Ended last week',
    'Starts next month',
    'On hold',
    'Zulrah sprint',
    'Invite only night',
    'Teams of four',
    'Photo finish',
];

const PAGES = {
    anonymous: () => [
        '/',
        '/osrs-snakes-and-ladders',
        '/osrs-clan-events',
        '/osrs-event-ideas',
        '/osrs-bingo',
        '/osrs-skill-race',
        '/osrs-drop-race',
        '/about',
        '/beta',
        '/privacy',
        '/terms',
        '/events',
        '/events/all',
        '/login',
        '/register',
        '/forgot-password',
        ...EVENT_TITLES.map((title) => `/events/${eventId(title)}`),
        `/events/${eventId('E2E Ladder')}/leaderboard`,
        `/events/${eventId('E2E Ladder')}/participants`,
    ],
    member: () => [
        '/my-events',
        '/community',
        '/teams',
        '/settings/profile',
        '/settings/account',
        '/settings/connections',
        '/settings/notifications',
        '/settings/animations',
    ],
    admin: () => [
        '/admin',
        '/admin/users',
        '/admin/events',
        '/admin/tasks',
        '/admin/boss-icons',
        '/admin/blueprints',
        '/admin/site',
        '/admin/content',
        '/admin/content/privacy',
        '/admin/invites',
        '/admin/audit',
        '/admin/diagnostics',
    ],
};

/** The three numbers of the pass, plus the width the browser really has. */
function measure() {
    const root = document.documentElement;
    const wide = [...document.querySelectorAll('body *')]
        .filter((element) => element.getBoundingClientRect().right > root.clientWidth + 1)
        .slice(0, 3)
        .map((element) => `${element.tagName.toLowerCase()}.${String(element.className).split(' ')[0]}`.slice(0, 50));
    const small = [...document.querySelectorAll('button, a[href]')].filter((element) => {
        const box = element.getBoundingClientRect();

        return box.width > 0 && box.height > 0 && box.height < 44;
    }).length;

    return { width: root.clientWidth, overflow: root.scrollWidth - root.clientWidth, wide, under44: small };
}

for (const [group, pages] of Object.entries(PAGES)) {
    for (const width of WIDTHS) {
        test(`${group} pages fit at ${width}px`, async ({ as }) => {
            const page = await as(group === 'anonymous' ? 'unreachable' : group);

            // Anonymous pages are read signed in as well: what a signed-in
            // reader sees is a superset of what a visitor sees, and one seat
            // keeps the run short. Login and register bounce a signed-in
            // account, so those two are read signed out below.
            await page.setViewportSize({ width, height: 800 });

            const problems = [];
            const touch = [];

            for (const path of pages()) {
                if (group === 'anonymous' && ['/login', '/register', '/forgot-password'].includes(path)) continue;

                await page.goto(path);
                await hydrated(page);

                const result = await page.evaluate(measure);

                if (result.width !== width) problems.push(`${path}: browser is ${result.width}px wide, not ${width}px`);
                if (result.overflow !== 0) problems.push(`${path}: overflows by ${result.overflow}px (${result.wide.join(', ')})`);
                if (width === 375 && result.under44 > 0) touch.push(`${path}: ${result.under44}`);
            }

            test.info().annotations.push({ type: 'under 44px at 375', description: touch.join('; ') || 'none' });

            expect(problems).toEqual([]);
        });
    }
}

for (const width of WIDTHS) {
    test(`the signed-out forms fit at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 800 });

        const problems = [];

        for (const path of ['/login', '/register', '/forgot-password']) {
            await page.goto(path);
            await hydrated(page);

            const result = await page.evaluate(measure);

            if (result.width !== width) problems.push(`${path}: browser is ${result.width}px wide, not ${width}px`);
            if (result.overflow !== 0) problems.push(`${path}: overflows by ${result.overflow}px (${result.wide.join(', ')})`);
        }

        expect(problems).toEqual([]);
    });
}
