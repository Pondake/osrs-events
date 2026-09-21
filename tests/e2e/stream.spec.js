import { test, expect, hydrated } from './fixtures.js';
import { PHP_WORKERS } from './support/env.js';
import { clearThrottles, eventId, resetEvent } from './support/db.js';

/**
 * The live channel as it really is: an open event page, one PHP worker held
 * for it, and somebody else acting on the same event. Every other spec answers
 * the channel with an empty stream (see fixtures.js), so none of them can see
 * a card that stops following what other people do, or an indicator that says
 * "live" about a connection that is not.
 *
 * Its own event ("E2E Stream") that no other spec plays, so what a viewer sees
 * change here is always this spec's doing.
 *
 * Needs a server that answers more than one request at a time: on Linux that
 * is PHP's own workers, on Windows E2E_PHP_WORKERS=4 (see support/env.js).
 */
const EVENT = 'E2E Stream';

test.use({ stream: 'live' });
test.skip(PHP_WORKERS < 2, 'needs a server that answers several requests at once (E2E_PHP_WORKERS=4 on Windows)');

test.beforeEach(() => {
    clearThrottles();
    resetEvent(EVENT);
});

async function open(page) {
    await page.goto(`/events/${eventId(EVENT)}`);
    await hydrated(page);
}

test('an open card follows what another player does, without a reload', async ({ page, as }) => {
    const player = await as('member');

    await open(page);

    // Connected, and saying so — not the stub's "reconnecting" after six seconds.
    await expect(page.locator('[title="Updating live"]').first()).toBeVisible();
    await expect(page.getByText('Nobody has marked a square yet.')).toBeVisible();

    // The same document all the way through: what changes is the data, not the page.
    await page.evaluate(() => (window.__sameDocument = true));

    await open(player);
    await player.getByRole('button', { name: 'Square 1', exact: true }).click();
    await player.getByRole('dialog').getByRole('button', { name: 'Mark as done' }).click();
    await expect(player.getByText('Square marked').first()).toBeVisible();

    // The channel polls every few seconds; the viewer sees it without asking.
    await expect(page.getByText('1 pts')).toBeVisible({ timeout: 20_000 });
    await expect(page.getByText('Nobody has marked a square yet.')).toHaveCount(0);

    // And the other way: taking it back reaches the viewer too.
    await player.getByRole('button', { name: 'Open your claim for this square' }).click();
    await player.getByRole('dialog').getByRole('button', { name: 'Withdraw claim' }).click();
    await expect(player.getByText('Square cleared').first()).toBeVisible();

    await expect(page.getByText('Nobody has marked a square yet.')).toBeVisible({ timeout: 20_000 });

    expect(await page.evaluate(() => window.__sameDocument)).toBe(true);
    await expect(page.getByText('Reconnecting…')).toHaveCount(0);
});
