import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, run } from './support/db.js';

/**
 * The notification settings a person keeps for themselves. Two things are
 * asked of this page and they are independent: which kinds of push an account
 * wants (server state, saved with the form), and whether this browser can
 * receive any (browser state, which the page has to be honest about).
 *
 * The suite runs without VAPID keys, which is the state of a fresh clone and a
 * fresh deploy: nothing can be delivered, and the page must say so rather than
 * offer buttons that cannot work.
 */
test.beforeEach(() => {
    clearThrottles();
    run("UPDATE users SET notification_preferences = NULL WHERE discord_username = 'e2e_notifier'");
});

/** The catalogue as the page was given it: key, label, and the default it ships with. */
async function categories(page) {
    return page.evaluate(() => {
        const script = document.querySelector('script[data-page="app"]');

        return JSON.parse(script ? script.textContent : document.querySelector('#app').dataset.page).props.categories;
    });
}

const toggle = (page, label) => page.getByRole('switch', { name: label });
const save = (page) => page.getByRole('button', { name: 'Save', exact: true });

async function open(page) {
    await page.goto('/settings/notifications');
    await hydrated(page);
    await expect(page.getByRole('switch').first()).toBeVisible();
}

test('every category starts where the catalogue says, and the chatty ones start off', async ({ as }) => {
    const page = await as('notifier');

    await open(page);

    const catalogue = await categories(page);

    expect(catalogue.length).toBeGreaterThan(5);

    for (const { label, default: on } of catalogue) {
        await expect(toggle(page, label), label).toHaveAttribute('aria-checked', String(on));
    }

    // Anything that can fire often ships off: permission to notify is not
    // permission to notify about everything.
    const off = catalogue.filter((category) => !category.default).map((category) => category.key);

    expect(off).toEqual(expect.arrayContaining(['rolls_available', 'rank_change', 'team_activity']));
});

test('a change is saved, survives a reload, and switching back reads as no change', async ({ as }) => {
    const page = await as('notifier');

    await open(page);

    const catalogue = await categories(page);
    const label = (key) => catalogue.find((category) => category.key === key).label;

    // Nothing to save until something changed, and changing back undoes it.
    await expect(save(page)).toBeDisabled();

    await toggle(page, label('rank_change')).click();
    await expect(save(page)).toBeEnabled();
    await toggle(page, label('rank_change')).click();
    await expect(save(page)).toBeDisabled();

    await toggle(page, label('rank_change')).click();
    await toggle(page, label('claim_reviewed')).click();
    await save(page).click();

    await expect(page.getByText('Notification settings saved.').first()).toBeVisible();

    await page.reload();
    await hydrated(page);
    await expect(page.getByRole('switch').first()).toBeVisible();

    await expect(toggle(page, label('rank_change'))).toHaveAttribute('aria-checked', 'true');
    await expect(toggle(page, label('claim_reviewed'))).toHaveAttribute('aria-checked', 'false');
    // What was not touched is still what the catalogue says.
    await expect(toggle(page, label('team_activity'))).toHaveAttribute('aria-checked', 'false');
    await expect(toggle(page, label('event_result'))).toHaveAttribute('aria-checked', 'true');
    await expect(save(page)).toBeDisabled();
});

test('with no server keys the page says so and offers nothing that cannot work', async ({ as }) => {
    const page = await as('notifier');

    await open(page);

    await expect(page.getByText('The server cannot send notifications yet')).toBeVisible();
    await expect(page.getByText('No keys configured')).toBeVisible();

    // No dead controls: nothing to turn on or off for this browser, no test push to send.
    await expect(page.getByRole('button', { name: 'Turn on' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Turn off' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Send a test' })).toHaveCount(0);

    // What is offered is still real: the preferences are kept for when keys exist.
    await expect(page.getByRole('switch').first()).toBeEnabled();
});
