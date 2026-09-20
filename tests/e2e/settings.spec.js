import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, run, user } from './support/db.js';

/**
 * The pages a signed-in account keeps for itself, and the one place an admin
 * changes what somebody else may do. Each spec that changes an account uses
 * an account of its own (see E2eSeeder), so nothing here is read by another
 * spec afterwards.
 */
test.beforeEach(() => clearThrottles());

test.describe('the email address', () => {
    test('a Discord login adds one without a password, and the password form opens up', async ({ as }) => {
        const page = await as('settler');

        await page.goto('/settings/account');
        await hydrated(page);

        await expect(page.getByText('Add an email address first')).toBeVisible();
        await expect(page.getByLabel('Current password')).toHaveCount(0);

        await page.getByRole('textbox').first().fill('settler@example.com');
        await page.getByRole('button', { name: 'Save email' }).click();

        await expect(page.getByText('Email address saved!').first()).toBeVisible();
        expect(user('e2e_settler').email).toBe('settler@example.com');

        await page.reload();
        await hydrated(page);
        await expect(page.getByText('Add an email address first')).toHaveCount(0);
    });

    test('changing it needs the password, and a wrong one changes nothing', async ({ as }) => {
        const page = await as('changer', { allow: [[422, /settings\/account\/email/]], console: [/current password/i] });

        await page.goto('/settings/account');
        await hydrated(page);

        const email = page.getByRole('textbox').first();

        await email.fill('changed@example.com');
        await page.getByLabel('Current password').first().fill('not-the-password');
        await page.getByRole('button', { name: 'Save email' }).click();

        await expect(page.getByText(/password is incorrect|does not match/i).first()).toBeVisible();
        expect(user('e2e_changer').email).toBe('changer@e2e.test');
    });

    test('changing it with the right password does', async ({ as }) => {
        const page = await as('changer');

        await page.goto('/settings/account');
        await hydrated(page);

        await page.getByRole('textbox').first().fill('changed@example.com');
        await page.getByLabel('Current password').first().fill('E2e-Password-1');
        await page.getByRole('button', { name: 'Save email' }).click();

        await expect(page.getByText('Email address saved!').first()).toBeVisible();
        expect(user('e2e_changer').email).toBe('changed@example.com');
    });
});

test('the intro can be played again from the profile', async ({ as }) => {
    const page = await as('settler');

    await page.goto('/settings/profile');
    await hydrated(page);

    await page.getByRole('button', { name: 'Replay the intro' }).click();

    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('dialog').getByRole('heading', { name: /welcome/i })).toBeVisible();

    await page.getByRole('dialog').getByText('Skip intro').click();
    await expect(page.getByRole('dialog')).toBeHidden();
});

test.describe('closing the account', () => {
    test('asks for the OSRS name, and then the account is gone', async ({ as }) => {
        const page = await as('leaver');

        await page.goto('/settings/account');
        await hydrated(page);

        await page.getByRole('button', { name: 'Delete account', exact: true }).click();

        const dialog = page.getByRole('dialog');
        const confirm = dialog.getByRole('button', { name: /delete/i }).last();

        await expect(dialog).toBeVisible();
        await expect(confirm).toBeDisabled();

        await dialog.getByRole('textbox').last().fill('E2E Leaver');
        await confirm.click();

        await expect(page).toHaveURL(/\/$/);
        await expect(page.getByRole('link', { name: 'Login' }).first()).toBeVisible();
        expect(user('e2e_leaver')).toBeUndefined();
    });
});

test('an admin can let a member create events, and the member is then offered to', async ({ as }) => {
    const admin = await as('admin');
    const member = await as('promotee');

    await member.goto('/events');
    await hydrated(member);
    await expect(member.getByRole('button', { name: 'Create event' })).toHaveCount(0);

    await admin.goto('/admin/users');
    await hydrated(admin);

    const row = admin.locator('li, div').filter({ hasText: '@e2e_promotee' }).filter({ has: admin.getByRole('button', { name: 'Edit' }) }).last();

    await row.getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('menuitem', { name: 'Grant: canCreateBoards' }).click();

    await expect(row.getByText('canCreateBoards')).toBeVisible();

    await member.reload();
    await hydrated(member);
    await expect(member.getByRole('button', { name: 'Create event' })).toBeVisible();

    // And back, so the permission is not left behind for a later run of this
    // spec against a database that has been kept.
    run('DELETE FROM model_has_permissions WHERE model_uuid = ?', [user('e2e_promotee').id]);
});
