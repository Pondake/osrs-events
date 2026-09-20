import { mkdirSync } from 'node:fs';
import { test as setup } from '@playwright/test';
import { artisanAsync } from './support/artisan.js';
import { AUTH_DIR, ORIGIN, authFile } from './support/env.js';
import { SEATS, STATES } from './support/seats.js';

/**
 * Signs every account in once and keeps the session, so no spec spends a
 * page load on logging in and none types a password.
 *
 * The link is the app's own local-only signed sign-in (`dev:login-link`),
 * the one the manual walkthrough uses.
 */
setup('sign every account in', async ({ browser }) => {
    mkdirSync(AUTH_DIR, { recursive: true });

    const accounts = {
        ...Object.fromEntries(Object.entries(SEATS).map(([name, seat]) => [name, seat.username])),
        ...Object.fromEntries(Object.entries(STATES).map(([name, state]) => [name, state.username ?? state.email])),
    };

    // One artisan boot per link is seconds each; together it is the slowest of them.
    const links = await Promise.all(
        Object.entries(accounts).map(async ([name, account]) => [
            name,
            (await artisanAsync('dev:login-link', account, `--base=${ORIGIN}`)).trim(),
        ]),
    );

    for (const [name, link] of links) {
        const context = await browser.newContext();
        const page = await context.newPage();

        await page.goto(link);
        await context.storageState({ path: authFile(name) });
        await context.close();
    }
});
