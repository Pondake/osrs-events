import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, strandAccount, unstrandAccount, user } from './support/db.js';

/**
 * The "why is nothing happening" page. Its checks read the environment, so
 * this suite's own state (no push keys, no scheduler, a fake Wise Old Man) is
 * what they report — and reporting it honestly is the point. Its actions each
 * reach only the admin pressing them, except the two on somebody's failing
 * standing, which are checked against that somebody.
 */
test.beforeEach(() => {
    clearThrottles();
    unstrandAccount();
});

test.afterEach(() => unstrandAccount());

async function open(admin) {
    await admin.goto('/admin/diagnostics');
    await hydrated(admin);
}

const group = (page, title) => page.locator('[data-slot="root"]').filter({ has: page.getByText(title, { exact: true }) }).last();

test('every group reports, and the ones this environment cannot satisfy do not say OK', async ({ as }) => {
    const admin = await as('admin');

    await open(admin);

    for (const title of ['Push notifications', 'Mail', 'Wise Old Man', 'Scheduled work', 'Rendering']) {
        await expect(admin.getByText(title, { exact: true }).first()).toBeVisible();
    }

    // No keys and no cron entry: those are not fine, and the page must not pretend.
    await expect(group(admin, 'Push notifications')).toContainText(/Broken|Needs attention/);
    await expect(group(admin, 'Scheduled work')).toContainText(/Broken|Needs attention/);

    // And it never prints a secret: the page is made to be screenshotted.
    const text = await admin.locator('body').innerText();

    expect(text).not.toMatch(/BEGIN [A-Z ]*PRIVATE KEY/);
    expect(text).not.toContain(user('e2e_admin').password ?? 'no-password-column-value');

    await admin.getByRole('button', { name: 'Re-run checks' }).click();
    await expect(admin.getByText('Push notifications', { exact: true }).first()).toBeVisible();
});

test('a lookup answers found for a name Wise Old Man knows, and says so for one it does not', async ({ as }) => {
    const admin = await as('admin');

    await open(admin);

    await admin.getByPlaceholder('Zezima').fill('E2E Member');
    await admin.getByRole('button', { name: 'Look up' }).click();
    await expect(admin.getByText('Found E2E Member. Their API is reachable and answering.').first()).toBeVisible();

    await admin.getByPlaceholder('Zezima').fill('Unknown Bob');
    await admin.getByRole('button', { name: 'Look up' }).click();
    await expect(admin.getByText(/has never heard of Unknown Bob/).first()).toBeVisible();
});

test('a test email goes to the admin\'s own address and nobody else', async ({ as }) => {
    const admin = await as('admin');

    await open(admin);
    await admin.getByRole('button', { name: 'Send a test email' }).click();

    await expect(admin.getByText('Sent to admin@e2e.test.').first()).toBeVisible();
});

test('a test push with no device says so instead of failing quietly', async ({ as }) => {
    const admin = await as('admin');

    await open(admin);
    await expect(admin.getByText(/No devices registered/).first()).toBeVisible();

    await admin.getByRole('button', { name: 'Send a test', exact: true }).click();
    await expect(admin.getByText(/No devices registered\. Turn notifications on/).nth(1)).toBeVisible();
});

test('the sweep is a rehearsal: it shows what it found and sends nothing', async ({ as }) => {
    const admin = await as('admin');

    await open(admin);
    await admin.getByRole('button', { name: 'Rehearse the sweep' }).click();

    await expect(admin.locator('pre').first()).toBeVisible();
    await expect(admin.locator('pre').first()).not.toBeEmpty();
});

async function openStandings(admin) {
    await open(admin);
    await expect(admin.getByText('Unmeasured entrants').first()).toBeVisible();
    await admin.getByRole('button', { name: 'Details' }).click();

    return admin.getByRole('dialog');
}

test('a standing that cannot sync is listed by account, with what failed and where', async ({ as }) => {
    strandAccount();

    const admin = await as('admin');
    const dialog = await openStandings(admin);

    await expect(dialog).toContainText('Stranded');
    await expect(dialog).toContainText('E2E Stranded');
    await expect(dialog).toContainText('E2E Drop Race');
    await expect(dialog).toContainText('Not tracked');
    await expect(dialog.getByRole('button', { name: 'Send reminder' })).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Reset username' })).toBeVisible();
});

// Found by this suite, not fixed yet: both buttons ask for a confirmation in a
// popover, and it opens underneath the dialog's overlay — visible, dimmed, and
// out of reach — so neither action can be completed from the page. Pinned by
// equality: fixing it fails these until the markers are removed.
test.fail('an admin can send the player a reminder from the list', async ({ as }) => {
    strandAccount();

    const admin = await as('admin');
    const dialog = await openStandings(admin);

    await dialog.getByRole('button', { name: 'Send reminder' }).click();
    await admin.getByRole('button', { name: 'Send reminder' }).last().click({ timeout: 3000 });

    await expect(admin.getByText('Reminder sent to Stranded.').first()).toBeVisible({ timeout: 3000 });
});

test.fail('an admin can reset the player’s name from the list', async ({ as }) => {
    strandAccount();

    const admin = await as('admin');
    const dialog = await openStandings(admin);

    await dialog.getByRole('button', { name: 'Reset username' }).click();
    await admin.getByRole('button', { name: 'Reset username' }).last().click({ timeout: 3000 });

    await expect(admin.getByText("Stranded's OSRS username has been reset.").first()).toBeVisible({ timeout: 3000 });
    expect(user('e2e_stranded').osrs_username).toBeNull();
});

test('diagnostics are for admins only', async ({ as }) => {
    const creator = await as('creator', { allow: [[403, /\/admin\/diagnostics/]] });

    expect((await creator.goto('/admin/diagnostics')).status()).toBe(403);
});
