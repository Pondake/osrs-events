import { test, expect, hydrated } from './fixtures.js';
import { addUser, query, resetUsers } from './support/db.js';

/**
 * The people an admin manages: roles, permissions, and removing an account.
 * Checked from both sides — the admin who changes it, and the account it
 * changes, which is where a permission is only real once a page opens or
 * refuses.
 *
 * Every account touched is one of its own (see E2eSeeder), or made and removed
 * here, so no other spec reads a changed user.
 */
test.beforeEach(() => resetUsers());
test.afterEach(() => resetUsers());

// The handle not followed by a digit, so "e2e_admin" is not also "e2e_admin2". (Badges follow the handle with no space, so a word-boundary test would fail.)
const row = (page, username) => page.locator('.divide-y > div').filter({ hasText: new RegExp(`@${username}(?!\\d)`) });

async function openUsers(admin, search) {
    await admin.goto('/admin/users');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by name or email…').fill(search);
    await expect(row(admin, search)).toBeVisible();
}

const actions = (admin, username) => row(admin, username).getByRole('button', { name: 'Edit' });

test('a role is granted, shown on the row, and taken away again', async ({ as }) => {
    const admin = await as('admin');

    await openUsers(admin, 'e2e_roled');
    await actions(admin, 'e2e_roled').click();
    await admin.getByRole('menuitem', { name: 'Add role: EDITOR' }).click();

    await expect(admin.getByText('Role EDITOR assigned.').first()).toBeVisible();
    await expect(row(admin, 'e2e_roled')).toContainText('EDITOR');

    await actions(admin, 'e2e_roled').click();
    await expect(admin.getByRole('menuitem', { name: 'Add role: EDITOR' })).toHaveCount(0);
    await admin.getByRole('menuitem', { name: 'Remove role: EDITOR' }).click();

    await expect(admin.getByText('Role EDITOR removed.').first()).toBeVisible();
    await expect(row(admin, 'e2e_roled')).not.toContainText('EDITOR');
});

test('a permission opens a page for the account it is given to, and only then', async ({ as }) => {
    const admin = await as('admin');
    const account = await as('roled', { allow: [[403, /\/admin\/(tasks|blueprints)/]] });

    expect((await account.goto('/admin/tasks')).status()).toBe(403);

    await openUsers(admin, 'e2e_roled');
    await actions(admin, 'e2e_roled').click();
    await admin.getByRole('menuitem', { name: 'Grant: canCreateTiles' }).click();

    await expect(admin.getByText('Permission granted.').first()).toBeVisible();
    await expect(row(admin, 'e2e_roled')).toContainText('canCreateTiles');

    expect((await account.goto('/admin/tasks')).status()).toBe(200);
    // Not the other one: a permission opens what it names.
    expect((await account.goto('/admin/blueprints')).status()).toBe(403);
});

test('the admin can delete an ordinary account, and it is gone', async ({ as }) => {
    addUser('e2e_victim', 'Victim');

    const admin = await as('admin');

    await openUsers(admin, 'e2e_victim');
    await actions(admin, 'e2e_victim').click();
    await admin.getByRole('menuitem', { name: 'Delete user' }).click();

    // Asks first, and names who.
    await expect(admin.getByRole('dialog')).toContainText('Victim');
    await admin.getByRole('dialog').getByRole('button', { name: 'Delete user' }).click();

    await expect(admin.getByText('User Victim deleted.').first()).toBeVisible();
    await expect(row(admin, 'e2e_victim')).toHaveCount(0);
    expect(query("SELECT COUNT(*) AS n FROM users WHERE discord_username = 'e2e_victim'")[0].n).toBe(0);
});

test('declining the delete keeps the account', async ({ as }) => {
    addUser('e2e_victim', 'Victim');

    const admin = await as('admin');

    await openUsers(admin, 'e2e_victim');
    await actions(admin, 'e2e_victim').click();
    await admin.getByRole('menuitem', { name: 'Delete user' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();

    await expect(row(admin, 'e2e_victim')).toBeVisible();
    expect(query("SELECT COUNT(*) AS n FROM users WHERE discord_username = 'e2e_victim'")[0].n).toBe(1);
});

test('an admin is never offered deleting, and cannot remove their own admin role', async ({ as }) => {
    addUser('e2e_admin2', 'Second Admin', { admin: true });

    const admin = await as('admin');

    // Another admin: roles can go, the account cannot be deleted from here.
    await openUsers(admin, 'e2e_admin2');
    await actions(admin, 'e2e_admin2').click();
    await expect(admin.getByRole('menuitem', { name: 'Remove role: ADMIN' })).toBeVisible();
    await expect(admin.getByRole('menuitem', { name: 'Delete user' })).toHaveCount(0);
    await admin.keyboard.press('Escape');

    // Yourself: neither.
    await openUsers(admin, 'e2e_admin');
    await actions(admin, 'e2e_admin').click();
    await expect(admin.getByRole('menuitem', { name: 'Remove role: ADMIN' })).toHaveCount(0);
    await expect(admin.getByRole('menuitem', { name: 'Delete user' })).toHaveCount(0);
});

test('the search narrows the list to who was asked for', async ({ as }) => {
    const admin = await as('admin');

    await admin.goto('/admin/users');
    await hydrated(admin);
    await expect(row(admin, 'e2e_member')).toBeVisible();

    await admin.getByPlaceholder('Search by name or email…').fill('e2e_creator');

    await expect(row(admin, 'e2e_creator')).toBeVisible();
    await expect(row(admin, 'e2e_member')).toHaveCount(0);

    await admin.getByPlaceholder('Search by name or email…').fill('nobody-has-this-name');
    await expect(admin.getByText('No users found')).toBeVisible();
});
