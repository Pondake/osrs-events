import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, eventId, resetInvites } from './support/db.js';

/**
 * An invite from the three places it lives: the host who hands it out, the
 * player who uses it, and the admin who can see every invite on the site and
 * pull one back. The end of the story is what matters — a revoked invite has
 * to stop working, and nobody who used it before is thrown out.
 */
const EVENT = 'Invite only night';

test.beforeEach(() => {
    clearThrottles();
    resetInvites(EVENT);
});

test.afterEach(() => resetInvites(EVENT));

async function open(page) {
    await page.goto(`/events/${eventId(EVENT)}`);
    await hydrated(page);
}

async function hostMakesInvite(host) {
    await open(host);
    await host.getByRole('button', { name: 'Manage invites' }).click();
    await host.getByRole('dialog').getByRole('button', { name: 'Create invite' }).click();

    const code = host.getByRole('dialog').locator('.font-mono').first();

    await expect(code).toBeVisible();

    return (await code.innerText()).trim();
}

async function useCode(player, code) {
    await open(player);
    await player.getByPlaceholder('Enter code').fill(code);
    await player.getByRole('button', { name: 'Join', exact: true }).click();
}

test('a code opens the event, and the admin sees who used it and can pull it back', async ({ as }) => {
    const host = await as('owner');
    const admin = await as('admin');
    const first = await as('member');
    const second = await as('creator', { console: [/./] });

    const code = await hostMakesInvite(host);

    expect(code).toMatch(/^[A-Z0-9]{6}$/i);

    // The first player is let in…
    await useCode(first, code);
    await expect(first.getByRole('button', { name: 'Leave event' })).toBeVisible();

    // …and the admin sees the invite, its event and that one person joined.
    await admin.goto('/admin/invites');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by label, code or event…').fill(code);

    const row = admin.locator('.divide-y > div').filter({ hasText: code });

    await expect(row).toContainText(EVENT);
    await expect(row).toContainText(/Joined\s*1/);

    // Revoked: the row is gone, and the code no longer opens the door.
    await row.getByRole('button', { name: 'Revoke invite' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Revoke invite' }).click();

    await expect(admin.getByText('No invites match these filters.')).toBeVisible();

    await useCode(second, code);
    await expect(second.getByText('Invite not found.')).toBeVisible();
    await expect(second.getByRole('button', { name: 'Leave event' })).toHaveCount(0);

    // Whoever used it before keeps their place.
    await open(first);
    await expect(first.getByRole('button', { name: 'Leave event' })).toBeVisible();
});

test('a code that does not exist says so and lets nobody in', async ({ as }) => {
    const player = await as('member', { console: [/./] });

    await useCode(player, 'NOPE00');

    await expect(player.getByText('Invite not found.')).toBeVisible();
    await expect(player.getByRole('button', { name: 'Leave event' })).toHaveCount(0);
});

test('the invite list is for admins only', async ({ as }) => {
    const member = await as('member', { allow: [[403, /\/admin\/invites/]] });

    expect((await member.goto('/admin/invites')).status()).toBe(403);
});
