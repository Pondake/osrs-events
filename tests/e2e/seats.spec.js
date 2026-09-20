import { test, expect, hydrated } from './fixtures.js';
import { eventId, query } from './support/db.js';

/**
 * What each seat is offered on the same event, one rung at a time. The ladder
 * is: anonymous, member, creator, cohost, owner, admin — each the one before
 * plus one thing — so a control that appears one rung early or late is the
 * finding, and a control that is offered and then refused is worse than one
 * that is missing.
 *
 * Whether the server refuses the request is PermissionMatrixTest's job. This
 * is about what a person sees and can reach.
 */
const LADDER = 'E2E Ladder';

async function openEvent(page, title = LADDER) {
    await page.goto(`/events/${eventId(title)}`);
    await hydrated(page);
}

const manage = (page) => page.getByRole('button', { name: 'Manage', exact: true });

test.describe('the event page', () => {
    test('a visitor can read it and is offered to join, nothing more', async ({ page }) => {
        await openEvent(page);

        await expect(page.getByRole('button', { name: 'Join event' }).first()).toBeVisible();
        await expect(manage(page)).toHaveCount(0);
    });

    test('a member is offered the same', async ({ as }) => {
        const page = await as('member');

        await openEvent(page);

        await expect(page.getByRole('button', { name: /^Join event|Leave event$/ }).first()).toBeVisible();
        await expect(manage(page)).toHaveCount(0);
    });

    test('a creator has no say over somebody else\'s event', async ({ as }) => {
        const page = await as('creator');

        await openEvent(page);

        await expect(manage(page)).toHaveCount(0);
    });

    test('a co-host can run it but is not offered to delete it', async ({ as }) => {
        const page = await as('cohost');

        await openEvent(page);
        await manage(page).click();

        for (const item of ['Edit tiles', 'Fill in tiles', 'Event settings', 'Event status', 'Review']) {
            await expect(page.getByRole('menuitem', { name: item })).toBeVisible();
        }

        await page.getByRole('menuitem', { name: 'Event status' }).click();
        await page.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();

        await expect(page.getByRole('dialog').getByRole('button', { name: 'Pause event' })).toBeVisible();
        await expect(page.getByRole('dialog').getByRole('button', { name: 'End event' })).toBeVisible();
        await expect(page.getByRole('dialog').getByRole('button', { name: 'Delete event' })).toHaveCount(0);
    });

    test('the owner is offered everything the co-host is, and deleting', async ({ as }) => {
        const page = await as('owner');

        await openEvent(page);
        await manage(page).click();
        await page.getByRole('menuitem', { name: 'Event status' }).click();
        await page.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();

        await expect(page.getByRole('dialog').getByRole('button', { name: 'Pause event' })).toBeVisible();
        await expect(page.getByRole('dialog').getByRole('button', { name: 'Delete event' })).toBeVisible();
    });

    test('an admin gets no host controls on the public side', async ({ as }) => {
        const page = await as('admin');

        await openEvent(page);

        // Using the power is a deliberate act in a place built for it.
        await expect(manage(page)).toHaveCount(0);
    });
});

test.describe('the events list', () => {
    test('only somebody who may create events is offered to', async ({ page, as }) => {
        await page.goto('/events');
        await hydrated(page);
        await expect(page.getByRole('button', { name: 'Create event' })).toHaveCount(0);

        const member = await as('member');

        await member.goto('/events');
        await hydrated(member);
        await expect(member.getByRole('button', { name: 'Create event' })).toHaveCount(0);

        const creator = await as('creator');

        await creator.goto('/events');
        await hydrated(creator);
        await expect(creator.getByRole('button', { name: 'Create event' })).toBeVisible();
    });
});

test.describe('the next rung up', () => {
    test('a member who opens the admin area is told plainly and shown a way out', async ({ as }) => {
        const page = await as('member', { allow: [[403, /\/admin/]] });

        const response = await page.goto('/admin');

        expect(response.status()).toBe(403);
        await expect(page.getByRole('link', { name: /home|browse events/i }).first()).toBeVisible();
    });

    test('the user menu offers the admin area to an admin and to nobody else', async ({ as }) => {
        for (const [seat, offered] of [['member', false], ['creator', false], ['admin', true]]) {
            const page = await as(seat);

            await page.goto('/events');
            await hydrated(page);
            await page.getByRole('button', { name: new RegExp(seat, 'i') }).first().click();

            const link = page.getByRole('menuitem', { name: 'Admin area' });

            if (offered) await expect(link).toBeVisible();
            else await expect(link).toHaveCount(0);
        }
    });
});

test.describe('making an event', () => {
    test('a creator makes one through the steps, runs it, and can delete it', async ({ as }) => {
        const page = await as('creator');

        await page.goto('/events');
        await hydrated(page);

        await page.getByRole('button', { name: 'Create event' }).click();

        const dialog = page.getByRole('dialog');

        await dialog.getByRole('button', { name: 'Next', exact: true }).click();

        // Whatever the type step offers, the default is a valid choice.
        await dialog.getByRole('button', { name: 'Next', exact: true }).click();

        await dialog.getByLabel('Title').first().fill('Made by the suite');
        await dialog.getByRole('button', { name: 'Next', exact: true }).click();
        await dialog.getByRole('button', { name: 'Next', exact: true }).click();
        await dialog.getByRole('button', { name: /^Create event$|^Create$/ }).click();

        // A new event opens straight into filling in its tiles.
        await expect(page.getByRole('dialog').getByText('Fill in the board')).toBeVisible();
        expect(query('SELECT COUNT(*) AS n FROM events WHERE title = ?', ['Made by the suite'])[0].n).toBe(1);

        await page.getByRole('dialog').getByRole('button', { name: 'Done' }).click();
        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('heading', { name: 'Made by the suite' })).toBeVisible();

        await manage(page).click();
        await page.getByRole('menuitem', { name: 'Event status' }).click();
        await page.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();

        // Deleting asks for the title to be typed, and stays disabled until it is.
        const remove = page.getByRole('dialog').getByRole('button', { name: 'Delete event' });

        await expect(remove).toBeDisabled();
        await page.getByRole('dialog').getByPlaceholder('Made by the suite').fill('Made by the suite');
        await remove.click();

        await expect.poll(() => query('SELECT COUNT(*) AS n FROM events WHERE title = ? AND deleted_at IS NULL', ['Made by the suite'])[0].n).toBe(0);
    });
});
