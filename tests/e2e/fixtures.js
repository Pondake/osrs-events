import { test as base, expect } from '@playwright/test';
import { authFile } from './support/env.js';

/**
 * Anything that fails a test without being asserted on: a script error, a
 * console error, or a request that came back 4xx/5xx. This is the net that
 * catches the bugs nobody wrote a test for — a panel that renders and then
 * 403s inside, a chunk that 404s after a deploy, a handler that throws on
 * click.
 *
 * A test that *expects* an error says so with `allow()`.
 */
export function watch(page) {
    const problems = [];
    const allowed = [];
    let documentsChecked = false;
    const allowedConsole = [];

    const isAllowed = (status, url) =>
        allowed.some((rule) => rule.status === status && rule.pattern.test(url));

    page.on('pageerror', (error) => problems.push(`uncaught: ${error.message}`));

    page.on('console', (message) => {
        if (message.type() !== 'error') return;

        const text = message.text();

        // The response listener below names the URL; this line only repeats it
        // without one.
        if (text.startsWith('Failed to load resource')) return;

        // The live channel is stubbed (see isolate), so it always reports
        // itself stale after six seconds. That is the stub, not a fault.
        if (text.includes('Event stream went stale')) return;

        if (allowedConsole.some((pattern) => pattern.test(text))) return;

        problems.push(`console: ${text}`);
    });

    page.on('response', (response) => {
        const status = response.status();
        const url = response.url();

        if (status < 400 || isAllowed(status, url)) return;

        // A test that walks pages and reports each one's status itself.
        if (documentsChecked && response.request().resourceType() === 'document') return;

        problems.push(`${status} ${response.request().method()} ${url}`);
    });

    page.on('requestfailed', (request) => {
        const reason = request.failure()?.errorText ?? '';

        // A navigation or a fetch cancelled by the next navigation.
        if (reason.includes('ERR_ABORTED')) return;

        problems.push(`request failed: ${request.method()} ${request.url()} (${reason})`);
    });

    return {
        problems,
        /**
         * The test reads the status of every page it loads and says which
         * were wrong, with more context than this net has. Sub-requests are
         * still checked.
         */
        documentsChecked() {
            documentsChecked = true;
        },
        /**
         * This test expects the app to log this. Every failed save is logged
         * with console.error on purpose, so a test that submits something
         * invalid sees one.
         */
        allowConsole(pattern) {
            allowedConsole.push(pattern);
        },
        /** This test expects `status` from URLs matching `pattern`. */
        allow(status, pattern) {
            allowed.push({ status, pattern });
        },
    };
}

/**
 * What the browser may and may not reach, for every context of the suite.
 *
 * PHP's built-in server is one process and every event page holds a
 * connection open for ~45 seconds, so one open event would freeze the rest of
 * the run. The channel is answered with an empty stream instead; what it
 * carries is covered by EventStreamTest on the server side.
 *
 * And nothing outside this site is fetched: a player's screenshot link, a web
 * font and an embedded image would otherwise make a run depend on other
 * people's servers being up, and on them not answering 403 to a bot.
 */
const EMPTY = {
    image: { contentType: 'image/gif', body: Buffer.from('R0lGODlhAQABAAAAACw=', 'base64') },
    stylesheet: { contentType: 'text/css', body: '' },
    script: { contentType: 'text/javascript', body: '' },
    font: { contentType: 'font/woff2', body: '' },
};

async function isolate(context, origin) {
    await context.route(/\/(events\/[^/]+\/stream|settings\/runelite\/stream)(\?|$)/, (route) =>
        route.fulfill({ status: 200, contentType: 'text/event-stream', body: 'retry: 3600000\n\n' }),
    );

    await context.route(
        (url) => ['http:', 'https:'].includes(url.protocol) && url.origin !== origin,
        (route) => route.fulfill({ status: 200, ...(EMPTY[route.request().resourceType()] ?? { contentType: 'text/plain', body: '' }) }),
    );
}

export const test = base.extend({
    watchers: async ({}, use) => {
        const list = [];

        await use(list);

        expect(
            list.flatMap((watcher) => watcher.problems),
            'the browser reported problems',
        ).toEqual([]);
    },

    page: async ({ page, watchers, baseURL }, use) => {
        await isolate(page.context(), new URL(baseURL).origin);
        watchers.push(watch(page));
        await use(page);
    },

    /** The watcher of the default page, for allow(). */
    watcher: async ({ page, watchers }, use) => {
        await use(watchers[watchers.length - 1]);
    },

    /**
     * A page signed in as a seat or an account state, on its own context so
     * two seats can be in one test.
     *
     *     const host = await as('owner');
     */
    as: async ({ browser, baseURL, viewport, colorScheme, watchers }, use) => {
        const contexts = [];

        await use(async (name, { allow = [], console: logged = [] } = {}) => {
            const context = await browser.newContext({ baseURL, viewport, colorScheme, storageState: authFile(name) });

            contexts.push(context);
            await isolate(context, new URL(baseURL).origin);

            const page = await context.newPage();
            const watcher = watch(page);

            allow.forEach(([status, pattern]) => watcher.allow(status, pattern));
            logged.forEach((pattern) => watcher.allowConsole(pattern));
            watchers.push(watcher);

            return Object.assign(page, { watcher });
        });

        await Promise.all(contexts.map((context) => context.close()));
    },
});

export { expect };

/** Waits until Vue has mounted something, so a link list is not read off an empty shell. */
export async function hydrated(page) {
    await page.waitForFunction(() => document.querySelector('#app')?.children.length > 0);
    await page.waitForLoadState('load');
}
