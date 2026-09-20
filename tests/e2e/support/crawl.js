import { hydrated } from '../fixtures.js';

/**
 * Links a person is never sent to by clicking around, or that leave the site,
 * or that do something rather than show something.
 */
const SKIP = [
    /^\/logout/,
    /^\/auth\//,
    /^\/dev\//,
    /^\/discord$/,
    /^\/sitemap\.xml/,
    /^\/onboarding\//,
    /^\/settings\/account\/discord/,
    /\/join\/[^/]+$/,
    /\.(xml|png|jpe?g|svg|ico|webmanifest|txt|json)$/i,
];

/** A same-site path without query or hash, or null for anything to leave alone. */
export function toPath(href, origin) {
    if (!href || href.startsWith('#') || /^(mailto|tel|javascript):/i.test(href)) return null;

    let url;

    try {
        url = new URL(href, origin);
    } catch {
        return null;
    }

    if (url.origin !== origin) return null;

    const path = url.pathname.replace(/\/+$/, '') || '/';

    return SKIP.some((pattern) => pattern.test(path)) ? null : path;
}

async function hrefs(page) {
    return page.$$eval('a[href]', (anchors) => anchors.map((anchor) => anchor.getAttribute('href')));
}

/**
 * The header's menus keep their items out of the page until they are opened,
 * so a crawl that only reads what is on screen never sees where they go.
 */
async function openedMenus(page) {
    const found = [];
    const triggers = page.locator('header [aria-haspopup="menu"], header button[aria-expanded]');

    for (let i = 0; i < (await triggers.count()); i++) {
        try {
            await triggers.nth(i).click({ timeout: 2000 });
            await page.waitForTimeout(150);
            found.push(...(await hrefs(page)));
            await page.keyboard.press('Escape');
        } catch {
            // A trigger that cannot be clicked is not a broken page.
        }
    }

    return found;
}

/**
 * Follows every link it can find, breadth first, the way a person would
 * click around — and reports each page that answered with an error, and which
 * page it was linked from. A link that leads somebody to a 403 is a bug even
 * when the 403 is correct.
 *
 * @returns {{visited: string[], broken: string[]}}
 */
export async function crawl(page, { start = '/', limit = 60 } = {}) {
    const queue = [[start, '(start)']];
    const seen = new Set([start]);
    const visited = [];
    const broken = [];
    let menusOpened = false;

    while (queue.length && visited.length < limit) {
        const [path, from] = queue.shift();
        const response = await page.goto(path);

        visited.push(path);

        if (!response || response.status() >= 400) {
            broken.push(`${response?.status() ?? 'no response'} ${path} (linked from ${from})`);

            continue;
        }

        await hydrated(page);

        const found = await hrefs(page);

        if (!menusOpened) {
            menusOpened = true;
            found.push(...(await openedMenus(page)));
        }

        for (const href of found) {
            const next = toPath(href, new URL(page.url()).origin);

            if (next && !seen.has(next)) {
                seen.add(next);
                queue.push([next, path]);
            }
        }
    }

    return { visited, broken, unvisited: queue.map(([path]) => path) };
}
