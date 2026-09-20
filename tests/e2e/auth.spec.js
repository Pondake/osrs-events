import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, userByEmail } from './support/db.js';
import { PASSWORD } from './support/seats.js';

/**
 * Getting in, getting out, and being turned away — through the forms, since
 * these are the only specs where the form itself is the thing under test.
 * Everywhere else a seat arrives already signed in.
 */

// Every form here counts against the same rate limit, so each test starts
// with a clean slate rather than inheriting the last one's hits.
test.beforeEach(() => clearThrottles());

// A retried attempt registers again, so it must not collide with the first.
const retry = () => (test.info().retry ? `-${test.info().retry}` : '');

test.describe('logging in', () => {
    test('with the right password lands on the events and shows who is signed in', async ({ page }) => {
        await page.goto('/login');
        await hydrated(page);

        await page.getByLabel('Email').fill('member@e2e.test');
        await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
        await page.getByRole('button', { name: 'Log in', exact: true }).click();

        await expect(page).toHaveURL(/\/events$/);
        await expect(page.getByRole('button', { name: 'Member' })).toBeVisible();
    });

    test('with the wrong password says so and stays on the form', async ({ page }) => {
        await page.goto('/login');
        await hydrated(page);

        await page.getByLabel('Email').fill('member@e2e.test');
        await page.getByLabel('Password', { exact: true }).fill('not-the-password');
        await page.getByRole('button', { name: 'Log in', exact: true }).click();

        await expect(page.getByText("These credentials don't match an account.")).toBeVisible();
        await expect(page).toHaveURL(/\/login$/);
    });

    test('a signed-in account is sent away from the login form', async ({ as }) => {
        const page = await as('member');

        await page.goto('/login');

        await expect(page).not.toHaveURL(/\/login/);
    });
});

// Signs in through the form rather than borrowing the saved session: logging
// out destroys the session server-side, and the saved one belongs to every
// other spec that uses this seat.
test('logging out returns to a signed-out site', async ({ page }) => {
    await page.goto('/login');
    await hydrated(page);

    await page.getByLabel('Email').fill('member@e2e.test');
    await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page).toHaveURL(/\/events$/);

    await page.getByRole('button', { name: 'Member' }).click();
    await page.getByRole('menuitem', { name: 'Logout' }).click();

    await expect(page.getByRole('link', { name: 'Login' }).first()).toBeVisible();

    await page.goto('/settings/account');
    await expect(page).toHaveURL(/\/login/);
});

test.describe('protected pages', () => {
    for (const path of ['/my-events', '/settings/profile', '/settings/account', '/community', '/teams', '/admin', '/admin/users']) {
        test(`${path} sends a signed-out visitor to the login form`, async ({ page }) => {
            await page.goto(path);

            await expect(page).toHaveURL(/\/login/);
            await expect(page.getByRole('heading', { name: 'Log in' })).toBeVisible();
        });
    }
});

test.describe('registering', () => {
    test('creates the account, signs it in and opens the intro', async ({ page }) => {
        await page.goto('/register');
        await hydrated(page);

        await page.getByLabel('Display name').fill('Registrant');
        await page.getByLabel('OSRS username').fill('Registrant');
        await page.getByLabel('Email').fill(`registrant${retry()}@e2e.test`);
        await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
        await page.getByLabel('Confirm password').fill(PASSWORD);
        await page.getByRole('button', { name: 'Create account', exact: true }).click();

        await expect(page).toHaveURL(/\/events$/);
        await expect(page.getByRole('dialog')).toBeVisible();

        const created = userByEmail(`registrant${retry()}@e2e.test`);

        expect(created.osrs_username).toBe('Registrant');
        expect(created.nickname).toBe('Registrant');
    });

    test('a name the hiscores tracker has never seen is still saved, with a warning', async ({ page }) => {
        await page.goto('/register');
        await hydrated(page);

        await page.getByLabel('Display name').fill('Newbie');
        await page.getByLabel('OSRS username').fill('Unknown Nb');
        await page.getByLabel('Email').fill(`newbie${retry()}@e2e.test`);
        await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
        await page.getByLabel('Confirm password').fill(PASSWORD);
        await page.getByRole('button', { name: 'Create account', exact: true }).click();

        await expect(page).toHaveURL(/\/events$/);
        await expect(page.getByText(/no record of that account yet/i).first()).toBeVisible();
        expect(userByEmail(`newbie${retry()}@e2e.test`).osrs_username).toBe('Unknown Nb');
    });

    test('an email that is taken is refused on the field', async ({ page }) => {
        await page.goto('/register');
        await hydrated(page);

        await page.getByLabel('Display name').fill('Copycat');
        await page.getByLabel('OSRS username').fill('Copycat');
        await page.getByLabel('Email').fill('member@e2e.test');
        await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
        await page.getByLabel('Confirm password').fill(PASSWORD);
        await page.getByRole('button', { name: 'Create account', exact: true }).click();

        await expect(page.getByText(/already been taken/i)).toBeVisible();
        await expect(page).toHaveURL(/\/register$/);
    });

    test('a password that is too weak is refused on the field', async ({ page }) => {
        await page.goto('/register');
        await hydrated(page);

        await page.getByLabel('Display name').fill('Weak');
        await page.getByLabel('OSRS username').fill('Weak');
        await page.getByLabel('Email').fill('weak@e2e.test');
        await page.getByLabel('Password', { exact: true }).fill('abc');
        await page.getByLabel('Confirm password').fill('abc');
        await page.getByRole('button', { name: 'Create account', exact: true }).click();

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.getByText(/at least 8 characters/i)).toBeVisible();
    });
});

test('asking for a password reset confirms it without saying whether the account exists', async ({ page }) => {
    clearThrottles();

    await page.goto('/forgot-password');
    await hydrated(page);

    await page.getByLabel('Email').fill('nobody@e2e.test');
    await page.getByRole('button', { name: 'Email a reset link' }).click();

    await expect(page.getByText('If that email has an account, a reset link is on its way.')).toBeVisible();
});

/**
 * Open. Every `throttle:N,M` on a route, without a prefix or a name, counts
 * into ONE bucket per address (per account, when signed in) — so the limit of
 * the strictest route in a group applies to all of them. Three wrong
 * passwords use up the three requests a reset link is allowed, and the person
 * who most needs the link gets "Slow down a moment" instead.
 *
 * `test.fail` keeps the suite green while this stands, and turns red the
 * moment it is fixed, so the marker gets removed with the fix.
 */
test.fail('three wrong passwords do not use up the requests a reset link is allowed', async ({ page, watcher }) => {
    watcher.allow(429, /forgot-password/);
    clearThrottles();

    for (let attempt = 0; attempt < 3; attempt++) {
        await page.goto('/login');
        await hydrated(page);
        await page.getByLabel('Email').fill('member@e2e.test');
        await page.getByLabel('Password', { exact: true }).fill('wrong-password');
        await page.getByRole('button', { name: 'Log in', exact: true }).click();
        await expect(page.getByText("These credentials don't match an account.")).toBeVisible();
    }

    await page.goto('/forgot-password');
    await hydrated(page);
    await page.getByLabel('Email').fill('member@e2e.test');
    await page.getByRole('button', { name: 'Email a reset link' }).click();

    await expect(page.getByText('If that email has an account, a reset link is on its way.')).toBeVisible();
});
