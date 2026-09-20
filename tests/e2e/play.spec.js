import { test, expect, hydrated } from './fixtures.js';
import { eventId, query } from './support/db.js';

/**
 * Taking part in an event as two people at once: a player who joins and
 * claims a tile, and the host who is asked to check it. The things a page's
 * own tests cannot see are the ones between the two — a claim the host never
 * sees, an approval the player is never told about.
 */
const LADDER = 'E2E Ladder';

const position = (username) =>
    query(
        `SELECT pb.current_position AS position FROM player_boards pb
         JOIN users u ON u.id = pb.user_id
         JOIN boards b ON b.id = pb.board_id
         JOIN events e ON e.id = b.event_id
         WHERE u.discord_username = ? AND e.title = ?`,
        [username, LADDER],
    )[0]?.position;

test('a signed-out visitor who tries to join is taken to the login form', async ({ page }) => {
    await page.goto(`/events/${eventId(LADDER)}`);
    await hydrated(page);

    await page.getByRole('button', { name: 'Join event' }).first().click();

    await expect(page).toHaveURL(/\/login/);
});

test('joining, leaving and joining again', async ({ as }) => {
    const page = await as('creator');

    await page.goto(`/events/${eventId(LADDER)}`);
    await hydrated(page);

    await page.getByRole('button', { name: 'Join event' }).first().click();
    await expect(page.getByRole('button', { name: 'Leave event' })).toBeVisible();

    await page.getByRole('link', { name: 'Participants' }).click();
    await expect(page.getByText('Creator', { exact: true }).first()).toBeVisible();

    await page.goto(`/events/${eventId(LADDER)}`);
    await hydrated(page);
    await page.getByRole('button', { name: 'Leave event' }).click();

    const confirm = page.getByRole('dialog').getByRole('button', { name: /leave/i });

    if (await confirm.isVisible().catch(() => false)) await confirm.click();

    await expect(page.getByRole('button', { name: 'Join event' }).first()).toBeVisible();
});

test('a claim reaches the host, and the player can roll once it is approved', async ({ as }) => {
    const player = await as('member');
    const host = await as('owner');

    await player.goto(`/events/${eventId(LADDER)}`);
    await hydrated(player);

    await player.getByRole('button', { name: 'Join event' }).first().click();
    await expect(player.getByRole('button', { name: 'Leave event' })).toBeVisible();

    await player.getByRole('button', { name: 'Mark as complete' }).click();
    await player.getByLabel('Screenshot link').fill('https://i.imgur.com/e2e.png');
    await player.getByRole('button', { name: 'Submit claim' }).click();

    await expect(player.getByText('Claim submitted for review').first()).toBeVisible();

    // The host, on their own page, is told there is something to check.
    await host.goto(`/events/${eventId(LADDER)}`);
    await hydrated(host);

    await host.getByRole('button', { name: /waiting for review/i }).click();
    await expect(host.getByRole('dialog').getByText('Review claims')).toBeVisible();
    await host.getByRole('dialog').getByRole('button', { name: 'Approve' }).first().click();
    await expect(host.getByText('Claim approved').first()).toBeVisible();

    // And the player can now move.
    await player.reload();
    await hydrated(player);

    const before = position('e2e_member');

    await player.getByRole('button', { name: '3', exact: true }).first().click();

    await expect.poll(() => position('e2e_member')).not.toBe(before);
});
