import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, query, resetSettings } from './support/db.js';

/**
 * The site settings an admin changes without a deploy, checked from both sides
 * of each: the admin who flips it, and the visitor or member it is about.
 *
 * Whatever a test changes is put back afterwards — through the database, not
 * through the page, so a test that dies half way cannot leave the door locked
 * for every spec that runs after it.
 */
test.beforeEach(() => {
    clearThrottles();
    resetSettings();
});

test.afterEach(() => resetSettings());

async function openSite(admin, section) {
    await admin.goto('/admin/site');
    await hydrated(admin);

    if (section) await admin.getByRole('button', { name: section, exact: true }).click();
}

const save = (admin) => admin.getByRole('button', { name: 'Save', exact: true });

test('an announcement appears for visitors, and is gone once it is cleared', async ({ as, page }) => {
    const admin = await as('admin');
    const visitor = page;

    await openSite(admin, 'Announcement');
    await admin.getByPlaceholder(/Summer bingo starts Friday/).fill('Summer bingo starts on Friday');
    await save(admin).click();
    await expect(admin.getByText('Site settings saved!').first()).toBeVisible();

    await visitor.goto('/about');
    await hydrated(visitor);
    await expect(visitor.getByText('Summer bingo starts on Friday')).toBeVisible();

    await openSite(admin, 'Announcement');
    await admin.getByPlaceholder(/Summer bingo starts Friday/).fill('');
    await save(admin).click();
    await expect(admin.getByText('Site settings saved!').first()).toBeVisible();

    await visitor.goto('/about');
    await hydrated(visitor);
    await expect(visitor.getByText('Summer bingo starts on Friday')).toHaveCount(0);
});

test('a setting that is not valid says which one and changes nothing', async ({ as }) => {
    const admin = await as('admin', { console: [/./] });

    await openSite(admin, 'Support');

    // Not a link at all is stopped by the browser's own check…
    await admin.getByPlaceholder('https://ko-fi.com/yourname').fill('not a link');
    await save(admin).click();
    expect(query("SELECT COUNT(*) AS n FROM settings WHERE key = 'kofi_url'")[0].n).toBe(0);

    // …and a link the browser accepts but the site does not is stopped by the server.
    await admin.getByPlaceholder('https://ko-fi.com/yourname').fill('ftp://example.com/x');
    await save(admin).click();

    await expect(admin.getByText(/Ko-fi profile URL.*valid URL/i).first()).toBeVisible();
    expect(query("SELECT COUNT(*) AS n FROM settings WHERE key = 'kofi_url'")[0].n).toBe(0);
});

test('the shared password door shuts visitors out, lets the right password in, and never shuts out an admin', async ({ as, page }) => {
    const admin = await as('admin');

    await openSite(admin, 'Access');
    await admin.locator('#setting-site_lock_enabled').getByRole('switch').click();
    await admin.locator('input[type="password"]').fill('door-password-1');
    await save(admin).click();
    await expect(admin.getByText('Site settings saved!').first()).toBeVisible();

    // A visitor is sent to the door…
    await page.goto('/events');
    await expect(page).toHaveURL(/\/locked/);
    await expect(page.getByRole('heading', { name: /not open yet/i })).toBeVisible();

    // …a wrong password does not open it…
    await page.getByPlaceholder('Password').fill('not-the-password');
    await page.getByRole('button', { name: 'Let me in' }).click();
    await expect(page.getByText('That password is not right.')).toBeVisible();

    // …the right one does…
    await page.getByPlaceholder('Password').fill('door-password-1');
    await page.getByRole('button', { name: 'Let me in' }).click();
    await expect(page).toHaveURL(/\/events/);

    // …and the admin who set it never met the door at all.
    await admin.goto('/events');
    await expect(admin).toHaveURL(/\/events/);
});

test('full lockdown turns a member away and leaves the admin in', async ({ as }) => {
    const admin = await as('admin');
    const member = await as('member');

    await openSite(admin, 'Access');
    await admin.locator('#setting-admin_lockdown_enabled').getByRole('switch').click();
    await save(admin).click();
    await expect(admin.getByText('Site settings saved!').first()).toBeVisible();

    await member.goto('/events');
    await expect(member).toHaveURL(/\/locked/);
    await expect(member.getByText(/taken offline/i).first()).toBeVisible();

    await admin.goto('/admin/users');
    await hydrated(admin);
    await expect(admin).toHaveURL(/\/admin\/users/);
});
