import { test, expect, hydrated } from './fixtures.js';
import { clearBossIcons, clearThrottles, eventId, query, suggestBossIcon } from './support/db.js';

/**
 * The pet a boss race shows. An icon is set by an admin and has to reach the
 * race page a player looks at; a suggestion from the weekly check changes
 * nothing until an admin looks at the picture and says yes.
 */
const ICON = 'https://oldschool.runescape.wiki/images/e2e-vorkath.png';

test.beforeEach(() => {
    clearThrottles();
    clearBossIcons();
});

test.afterEach(() => clearBossIcons());

const card = (page, boss) => page.locator('.grid > div').filter({ hasText: boss }).filter({ has: page.getByRole('button', { name: 'Set icon' }) });

async function open(admin) {
    await admin.goto('/admin/boss-icons');
    await hydrated(admin);
}

test('an icon set here is the one the boss race shows, and resetting takes it away', async ({ as, page }) => {
    const admin = await as('admin');

    await open(admin);
    await admin.getByPlaceholder('Search').fill('Vorkath');
    await card(admin, 'Vorkath').getByRole('button', { name: 'Set icon' }).click();

    const dialog = admin.getByRole('dialog');

    await dialog.getByPlaceholder(/oldschool.runescape.wiki/).fill(ICON);
    await dialog.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByText('Boss icon saved.').first()).toBeVisible();
    await expect(card(admin, 'Vorkath')).toContainText('Set by an admin');

    await page.goto(`/events/${eventId('E2E Drop Race')}`);
    await hydrated(page);
    await expect(page.locator(`img[src="${ICON}"]`).first()).toBeAttached();

    await open(admin);
    await admin.getByPlaceholder('Search').fill('Vorkath');
    await card(admin, 'Vorkath').getByRole('button', { name: 'Use default' }).click();

    await expect(admin.getByText('Boss icon reset to the default.').first()).toBeVisible();
    await expect(card(admin, 'Vorkath')).not.toContainText('Set by an admin');

    await page.reload();
    await hydrated(page);
    await expect(page.locator(`img[src="${ICON}"]`)).toHaveCount(0);
});

test('an address that is not a link is refused', async ({ as }) => {
    const admin = await as('admin', { console: [/./] });

    await open(admin);
    await admin.getByPlaceholder('Search').fill('Vorkath');
    await card(admin, 'Vorkath').getByRole('button', { name: 'Set icon' }).click();

    const dialog = admin.getByRole('dialog');

    await dialog.getByPlaceholder(/oldschool.runescape.wiki/).fill('not a link');
    await dialog.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(dialog).toBeVisible();
    expect(query('SELECT COUNT(*) AS n FROM boss_icons WHERE icon_url IS NOT NULL')[0].n).toBe(0);
});

test('a suggestion waits for an admin, and only a yes changes the icon', async ({ as }) => {
    suggestBossIcon('zulrah', 'https://oldschool.runescape.wiki/images/e2e-zulrah.png');
    suggestBossIcon('vorkath', 'https://oldschool.runescape.wiki/images/e2e-not-this.png');

    const admin = await as('admin');

    await open(admin);
    await expect(admin.getByText('Waiting on you (2)')).toBeVisible();

    // Nothing is applied until somebody says so.
    expect(query('SELECT COUNT(*) AS n FROM boss_icons WHERE icon_url IS NOT NULL')[0].n).toBe(0);

    const pending = (boss) => admin.locator('section .grid > div').filter({ hasText: boss });

    await pending('Zulrah').getByRole('button', { name: 'Use this' }).click();
    await expect(admin.getByText('Boss icon saved.').first()).toBeVisible();

    await pending('Vorkath').getByRole('button', { name: 'Not this one' }).click();
    await expect(admin.getByText('Suggestion dismissed.').first()).toBeVisible();

    const rows = Object.fromEntries(query('SELECT metric, icon_url, suggested_url, dismissed_url FROM boss_icons').map((row) => [row.metric, row]));

    expect(rows.zulrah.icon_url).toBe('https://oldschool.runescape.wiki/images/e2e-zulrah.png');
    expect(rows.zulrah.suggested_url).toBeNull();
    expect(rows.vorkath.icon_url).toBeNull();
    expect(rows.vorkath.dismissed_url).toBe('https://oldschool.runescape.wiki/images/e2e-not-this.png');
    await expect(admin.getByText('Waiting on you')).toHaveCount(0);
});

test('boss icons are for admins only', async ({ as }) => {
    const creator = await as('creator', { allow: [[403, /\/admin\/boss-icons/]] });

    expect((await creator.goto('/admin/boss-icons')).status()).toBe(403);
});
