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
