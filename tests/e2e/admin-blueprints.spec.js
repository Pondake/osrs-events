import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, removeE2eBlueprints } from './support/db.js';

/**
 * Blueprints are the formats a creator is offered when making an event, so
 * they are checked from both ends: the admin who keeps the list, and the
 * creator who sees it. A blueprint that is switched off must stop being
 * offered without being deleted, and one that is deleted must stop for good.
 */
test.beforeEach(() => {
    clearThrottles();
    removeE2eBlueprints();
});

test.afterEach(() => removeE2eBlueprints());

const row = (page, title) => page.locator('.divide-y > div').filter({ hasText: title });

async function create(admin, title, description) {
    await admin.goto('/admin/blueprints');
    await hydrated(admin);
    await admin.getByRole('button', { name: 'New blueprint' }).click();

    const dialog = admin.getByRole('dialog');

    await dialog.getByLabel('Name').fill(title);
    await dialog.getByLabel('Description').fill(description);
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();

    await expect(admin.getByText('Blueprint created!').first()).toBeVisible();
    await expect(row(admin, title)).toContainText(description);
}

async function offeredToCreator(creator) {
    await creator.goto('/events');
    await hydrated(creator);

    // The template step comes first, and it asks the server what to suggest.
    const answered = creator.waitForResponse((response) => response.url().includes('/event-blueprints'));

    await creator.getByRole('button', { name: 'Create event' }).click();
    await answered;
    await expect(creator.getByRole('dialog')).toBeVisible();

    return creator.getByRole('dialog').getByText('E2E Weekly Bingo').count().then((n) => n > 0);
}

test('a blueprint is made, offered to creators, retired, and deleted', async ({ as }) => {
    const admin = await as('admin');

    await create(admin, 'E2E Weekly Bingo', 'A card for the week.');

    const creator = await as('creator');

    expect(await offeredToCreator(creator)).toBe(true);

    // Retired: still in the admin's list, marked, and no longer offered.
    await row(admin, 'E2E Weekly Bingo').getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('dialog').getByRole('switch').click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByText('Blueprint updated!').first()).toBeVisible();
    await expect(row(admin, 'E2E Weekly Bingo')).toContainText('Not suggested');
    expect(await offeredToCreator(creator)).toBe(false);

    // Deleted: asks first, then it is gone.
    admin.once('dialog', (dialog) => dialog.accept());
    await row(admin, 'E2E Weekly Bingo').getByRole('button', { name: 'Delete' }).click();

    await expect(admin.getByText('Blueprint deleted.').first()).toBeVisible();
    await expect(row(admin, 'E2E Weekly Bingo')).toHaveCount(0);
});

test('declining the delete keeps the blueprint', async ({ as }) => {
    const admin = await as('admin');

    await create(admin, 'E2E Keep me', 'Stays.');

    admin.once('dialog', (dialog) => dialog.dismiss());
    await row(admin, 'E2E Keep me').getByRole('button', { name: 'Delete' }).click();

    await expect(row(admin, 'E2E Keep me')).toBeVisible();
});

test('the list is open to somebody who may create events, and to nobody who may not', async ({ as }) => {
    const creator = await as('creator');
    const member = await as('member', { allow: [[403, /\/admin\/blueprints/]] });

    expect((await creator.goto('/admin/blueprints')).status()).toBe(200);
    expect((await member.goto('/admin/blueprints')).status()).toBe(403);
});
