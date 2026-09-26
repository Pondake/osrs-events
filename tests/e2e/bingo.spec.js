import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, eventId, resetEvent } from './support/db.js';

/**
 * Playing a bingo card as the people it needs: the player who claims a square
 * and the host who is asked to check it. The seeded cards are 3x3 and won on a
 * line, so a whole line is three claims.
 *
 * What the page's own tests cannot see is what sits between the two: a claim
 * that scores before anybody has looked at it, a verdict the player is never
 * shown, a card that says somebody won when nothing was approved.
 */
const REVIEWED = 'E2E Bingo';
const INSTANT = 'E2E Bingo instant';

test.beforeEach(() => {
    clearThrottles();
    resetEvent(REVIEWED);
    resetEvent(INSTANT);
});

async function open(page, title) {
    await page.goto(`/events/${eventId(title)}`);
    await hydrated(page);
}

const square = (page, number) => page.getByRole('button', { name: `Square ${number}`, exact: true });

/** Claims a square and waits until the card shows it, so the next click is on a settled page. */
async function claim(page, number, alreadyClaimed = 0) {
    await square(page, number).click();

    const dialog = page.getByRole('dialog');

    await dialog.getByLabel('Screenshot link').fill('https://i.imgur.com/e2e.png');
    await dialog.getByRole('button', { name: 'Submit claim' }).click();

    await expect(page.getByRole('button', { name: 'Open your claim for this square' })).toHaveCount(alreadyClaimed + 1);
    await expect(dialog).toBeHidden();
}

async function openQueue(host) {
    await host.getByRole('button', { name: /waiting for review/i }).click();
    await expect(host.getByRole('dialog').getByText('Review claims')).toBeVisible();
}

test('a claim waits for the host, and only scores once it is approved', async ({ as }) => {
    const player = await as('member');
    const host = await as('owner');

    await open(player, REVIEWED);
    await claim(player, 1);

    // Sitting in the queue is not scoring.
    await expect(player.getByText('Nobody has marked a square yet.')).toBeVisible();

    await open(host, REVIEWED);
    await openQueue(host);
    await host.getByRole('dialog').getByRole('button', { name: 'Approve' }).click();
    await expect(host.getByText('Claim approved').first()).toBeVisible();
    await expect(host.getByRole('dialog').getByText('Nothing waiting for review')).toBeVisible();

    await player.reload();
    await hydrated(player);

    await expect(player.getByText('1 pt', { exact: true })).toBeVisible();
    await expect(player.getByText('1 of 9 squares')).toBeVisible();
    await expect(player.getByText('Nobody has marked a square yet.')).toHaveCount(0);
});

test('a rejected claim tells the player why, and scores nothing', async ({ as }) => {
    const player = await as('creator');
    const host = await as('owner');

    await open(player, REVIEWED);
    await claim(player, 2);

    await open(host, REVIEWED);
    await openQueue(host);
    await host.getByRole('dialog').getByLabel('Note (optional)').fill('The screenshot does not show the drop');
    await host.getByRole('dialog').getByRole('button', { name: 'Reject' }).click();
    await expect(host.getByText('Claim rejected').first()).toBeVisible();

    await player.reload();
    await hydrated(player);

    await expect(player.getByText('Your claim for "Square 2" was rejected')).toBeVisible();
    await expect(player.getByText('The screenshot does not show the drop')).toBeVisible();
    await expect(player.getByText('Nobody has marked a square yet.')).toBeVisible();
});

test('the host sees every player\'s claim in the same queue', async ({ as }) => {
    const first = await as('member');
    const second = await as('creator');
    const host = await as('owner');

    await open(first, REVIEWED);
    await claim(first, 4);
    await open(second, REVIEWED);
    await claim(second, 4);

    await open(host, REVIEWED);
    await expect(host.getByRole('button', { name: '2 waiting for review' })).toBeVisible();
});

test('three approved squares in a line win the card', async ({ as }) => {
    const player = await as('unreachable');
    const host = await as('owner');

    await open(player, REVIEWED);

    for (const [done, number] of [1, 2, 3].entries()) await claim(player, number, done);

    await expect(player.getByText('You won!')).toHaveCount(0);

    await open(host, REVIEWED);
    await openQueue(host);

    const approve = host.getByRole('dialog').getByRole('button', { name: 'Approve' });

    // The queue shrinks as it is worked through, so the same button is the next claim.
    for (const remaining of [3, 2, 1]) {
        await expect(host.getByRole('dialog').getByText(`1 / ${remaining}`, { exact: true })).toBeVisible();
        await approve.click();
    }

    await expect(host.getByRole('dialog').getByText('Nothing waiting for review')).toBeVisible();

    await player.reload();
    await hydrated(player);

    await expect(player.getByText('You won!')).toBeVisible();
    await expect(player.getByText('1 line')).toBeVisible();
    await expect(player.getByText('3 pts')).toBeVisible();
});

test('without review a claim counts at once, and can be withdrawn', async ({ as }) => {
    const player = await as('member');

    await open(player, INSTANT);

    await square(player, 1).click();
    await player.getByRole('dialog').getByRole('button', { name: 'Mark as done' }).click();

    await expect(player.getByText('Square marked').first()).toBeVisible();
    await expect(player.getByText('1 pt', { exact: true })).toBeVisible();

    await player.getByRole('button', { name: 'Open your claim for this square' }).click();
    await player.getByRole('dialog').getByRole('button', { name: 'Withdraw claim' }).click();

    await expect(player.getByText('Square cleared').first()).toBeVisible();
    await expect(player.getByText('Nobody has marked a square yet.')).toBeVisible();
});

test('a visitor sees the card and standings, and its squares are not offered', async ({ page }) => {
    await open(page, REVIEWED);

    await expect(page.getByText('Nobody has marked a square yet.')).toBeVisible();
    await expect(square(page, 5)).toBeDisabled();
    await expect(page.getByRole('button', { name: 'Join event' }).first()).toBeVisible();
});

test('a signed-in reader who has not joined can open a square, and claiming joins them', async ({ as }) => {
    const player = await as('cohost');

    await open(player, INSTANT);
    await expect(player.getByRole('button', { name: 'Join event' }).first()).toBeVisible();

    await square(player, 9).click();
    await player.getByRole('dialog').getByRole('button', { name: 'Mark as done' }).click();

    await expect(player.getByText('Square marked').first()).toBeVisible();
    await expect(player.getByRole('button', { name: 'Leave event' })).toBeVisible();
});

/**
 * Lockout, from both teams' side. The red seat plays for E2E Reds, the blue
 * seat for E2E Blues; the first team to have a square approved keeps it.
 */
const LOCKOUT = 'E2E Lockout';

test.describe('lockout', () => {
    test.beforeEach(() => resetEvent(LOCKOUT));

    test('the first claim in line takes the square, and the other team sees it taken', async ({ as }) => {
        const red = await as('red');
        const blue = await as('blue');
        const host = await as('owner');

        await open(red, LOCKOUT);
        await expect(red.getByText('Lockout: the first team to a square keeps it')).toBeVisible();
        await claim(red, 1);

        // Nobody holds it yet, so Blues can still get in line behind them.
        await open(blue, LOCKOUT);
        await claim(blue, 1);

        await open(host, LOCKOUT);
        await expect(host.getByRole('button', { name: '2 waiting for review' })).toBeVisible();
        await openQueue(host);

        const dialog = host.getByRole('dialog');

        // Blues' claim came second: it cannot be approved while Reds' waits.
        await dialog.getByRole('button', { name: 'Next claim' }).click();
        await expect(dialog.getByText('Not first in line')).toBeVisible();
        await expect(dialog.getByRole('button', { name: 'Approve' })).toBeDisabled();

        await dialog.getByRole('button', { name: 'Previous claim' }).click();
        await expect(dialog.getByText('Not first in line')).toHaveCount(0);
        await dialog.getByRole('button', { name: 'Approve' }).click();
        await expect(host.getByText('Claim approved').first()).toBeVisible();

        // Blues' claim leaves the queue: it is not a question any more.
        await expect(dialog.getByText('Nothing waiting for review')).toBeVisible();

        await red.reload();
        await hydrated(red);
        await expect(red.getByText('1 pt', { exact: true })).toBeVisible();

        await blue.reload();
        await hydrated(blue);
        await blue.getByRole('button', { name: 'Taken by E2E Reds' }).click();
        await expect(blue.getByRole('dialog').getByText('Your claim stays in line, and only counts if theirs is overturned.')).toBeVisible();
    });

    test('a square another team holds offers no claim', async ({ as }) => {
        const red = await as('red');
        const blue = await as('blue');
        const host = await as('owner');

        await open(red, LOCKOUT);
        await claim(red, 5);

        await open(host, LOCKOUT);
        await openQueue(host);
        await host.getByRole('dialog').getByRole('button', { name: 'Approve' }).click();
        await expect(host.getByText('Claim approved').first()).toBeVisible();

        await open(blue, LOCKOUT);
        await blue.getByRole('button', { name: 'Taken by E2E Reds' }).click();

        const dialog = blue.getByRole('dialog');

        await expect(dialog.getByText('Another team had this square approved first, so it can no longer be claimed.')).toBeVisible();
        await expect(dialog.getByRole('button', { name: 'Submit claim' })).toHaveCount(0);
        await expect(dialog.getByLabel('Screenshot link')).toHaveCount(0);
    });
});

/**
 * Reveal, from the host who draws and the players who may only see what was
 * drawn. The card is the Item Race format: lockout on, two squares at most,
 * claims count at once.
 */
const REVEAL = 'E2E Reveal';

/** Every square name anywhere in the page, the props it was rendered from included. */
async function namesIn(page) {
    return [...new Set((await page.content()).match(/Square \d/g) ?? [])];
}

test.describe('reveal', () => {
    test.beforeEach(() => resetEvent(REVEAL));

    test('the host draws squares one at a time, and players only ever see what was drawn', async ({ as }) => {
        const host = await as('owner');
        const red = await as('red');
        const blue = await as('blue');

        await open(red, REVEAL);
        await expect(red.getByRole('button', { name: 'Not revealed yet' })).toHaveCount(9);
        await expect(red.getByRole('button', { name: 'Not revealed yet' }).first()).toBeDisabled();
        expect(await namesIn(red)).toEqual([]);

        // The host sees the whole card, marked as hidden from players.
        await open(host, REVEAL);
        await expect(host.getByRole('button', { name: /^Hidden from players: Square \d$/ })).toHaveCount(9);
        await expect(host.getByText('0 revealed, 2 still to come')).toBeVisible();

        await host.getByRole('button', { name: 'Reveal next square' }).click();

        const toast = host.getByText(/^Revealed: Square \d$/).first();

        await expect(toast).toBeVisible();
        const drawn = (await toast.textContent()).match(/Square \d/)[0];

        await expect(host.getByText('1 revealed, 1 still to come')).toBeVisible();
        await expect(host.getByRole('button', { name: /^Hidden from players: Square \d$/ })).toHaveCount(8);

        await red.reload();
        await hydrated(red);
        await expect(red.getByRole('button', { name: 'Not revealed yet' })).toHaveCount(8);
        expect(await namesIn(red)).toEqual([drawn]);

        // Drawn, it plays like any other square, lockout included.
        await red.getByRole('button', { name: drawn, exact: true }).click();
        await red.getByRole('dialog').getByRole('button', { name: 'Mark as done' }).click();
        await expect(red.getByText('Square marked').first()).toBeVisible();

        await open(blue, REVEAL);
        expect(await namesIn(blue)).toEqual([drawn]);
        await expect(blue.getByRole('button', { name: 'Taken by E2E Reds' })).toBeVisible();

        // The second draw is the last one this card allows.
        await host.getByRole('button', { name: 'Reveal next square' }).click();
        await expect(host.getByText('All 2 squares are revealed')).toBeVisible();
        await expect(host.getByRole('button', { name: 'Reveal next square' })).toBeDisabled();

        await blue.reload();
        await hydrated(blue);
        await expect(blue.getByRole('button', { name: 'Not revealed yet' })).toHaveCount(7);
        expect(await namesIn(blue)).toHaveLength(2);
    });

    test('a player cannot draw, and has no button to', async ({ as }) => {
        const red = await as('red');

        await open(red, REVEAL);
        await expect(red.getByText('0 revealed, 2 still to come')).toBeVisible();
        await expect(red.getByRole('button', { name: 'Reveal next square' })).toHaveCount(0);

        const xsrf = (await red.context().cookies()).find((cookie) => cookie.name === 'XSRF-TOKEN');
        const response = await red.request.post(`/events/${eventId(REVEAL)}/bingo/reveal`, {
            headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.value) },
        });

        expect(response.status()).toBe(403);
    });
});
