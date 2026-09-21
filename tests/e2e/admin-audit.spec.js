import { test, expect, hydrated } from './fixtures.js';
import { addUser, clearThrottles, resetUsers } from './support/db.js';

/**
 * The record of what admins did. It is only worth having if what an admin does
 * ends up in it — with who did it and to whom, still readable after the
 * account it was about is gone — and if nobody can change it. So the entries
 * here are made by doing the things, not by writing rows.
 */
test.beforeEach(() => {
    clearThrottles();
    resetUsers();
});

test.afterEach(() => resetUsers());

const entry = (page, text) => page.locator('.divide-y > div:not([data-slot])').filter({ hasText: text });

async function usersRow(admin, username) {
    await admin.goto('/admin/users');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by name or email…').fill(username);

    const row = admin.locator('.divide-y > div:not([data-slot])').filter({ hasText: `@${username}` });

    await expect(row).toBeVisible();

    return row;
}

test('what an admin does is recorded with who did it and to whom', async ({ as }) => {
    const admin = await as('admin');

    const row = await usersRow(admin, 'e2e_roled');

    await row.getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('menuitem', { name: 'Add role: EDITOR' }).click();
    await expect(admin.getByText('Role EDITOR assigned.').first()).toBeVisible();

    await admin.goto('/admin/audit');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by who acted or who was affected…').fill('Roled');

    const logged = entry(admin, 'Role granted');

    await expect(logged.first()).toContainText('by Admin');
    await expect(logged.first()).toContainText('Roled');
});

test('an entry still names the account after it has been deleted', async ({ as }) => {
    addUser('e2e_victim', 'Victim');

    const admin = await as('admin');
    const row = await usersRow(admin, 'e2e_victim');

    await row.getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('menuitem', { name: 'Delete user' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Delete user' }).click();
    await expect(admin.getByText('User Victim deleted.').first()).toBeVisible();

    await admin.goto('/admin/audit');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by who acted or who was affected…').fill('Victim');

    await expect(entry(admin, 'User deleted').first()).toContainText('Victim');
});

test('the log can be narrowed by action, and says so when nothing matches', async ({ as }) => {
    const admin = await as('admin');
    const row = await usersRow(admin, 'e2e_roled');

    await row.getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('menuitem', { name: 'Grant: canCreateTiles' }).click();
    await expect(admin.getByText('Permission granted.').first()).toBeVisible();

    await admin.goto('/admin/audit?action=user.permission_granted');
    await hydrated(admin);

    const rows = admin.locator('.divide-y > div:not([data-slot])');

    await expect(rows.first()).toContainText('Permission granted');
    expect(await rows.filter({ hasNotText: 'Permission granted' }).count()).toBe(0);

    await admin.getByPlaceholder('Search by who acted or who was affected…').fill('nobody has this name');
    await expect(admin.getByText('No entries match these filters.')).toBeVisible();
});

test('the log is read-only: nothing on an entry edits or removes it', async ({ as }) => {
    const admin = await as('admin');
    const row = await usersRow(admin, 'e2e_roled');

    // An entry to look at, whatever ran before this test.
    await row.getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('menuitem', { name: 'Add role: EDITOR' }).click();
    await expect(admin.getByText('Role EDITOR assigned.').first()).toBeVisible();

    await admin.goto('/admin/audit');
    await hydrated(admin);

    const rows = admin.locator('.divide-y > div:not([data-slot])');

    expect(await rows.count()).toBeGreaterThan(0);
    await expect(rows.getByRole('button')).toHaveCount(0);
    await expect(rows.getByRole('link', { name: /delete|remove|edit/i })).toHaveCount(0);
});

test('the log is for admins only', async ({ as }) => {
    const creator = await as('creator', { allow: [[403, /\/admin\/audit/]] });

    expect((await creator.goto('/admin/audit')).status()).toBe(403);
});
