import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, eventId, query, resetAdminEvent, run } from './support/db.js';

/**
 * The admin's own list of events: the one place an admin edits, pauses, deletes
 * and restores an event they did not make, and the only place a deleted event
 * can be found at all. On the public side an admin is an ordinary reader, so
 * everything here is checked from both — what the admin changes, and what a
 * visitor then gets.
 *
 * Works on an event of its own (see E2eSeeder), put back afterwards.
 */
const TITLE = 'E2E Admin Event';

test.beforeEach(() => {
    clearThrottles();
    resetAdminEvent();
});

test.afterEach(() => resetAdminEvent());

const row = (page, title = TITLE) => page.locator('.divide-y > div:not([data-slot])').filter({ hasText: title });

async function openList(admin) {
    await admin.goto('/admin/events');
    await hydrated(admin);
    await admin.getByPlaceholder('Search by title…').fill('E2E Admin');
    await expect(row(admin)).toBeVisible();
}

async function pickStatus(admin, name) {
    await admin.getByRole('combobox').click();
    await admin.getByRole('option', { name, exact: true }).click();
}

test('the list names the event and its hosts, and the search finds it', async ({ as }) => {
    const admin = await as('admin');

    await openList(admin);
    await expect(row(admin)).toContainText('Owner');

    await admin.getByPlaceholder('Search by title…').fill('no event is called this');
    await expect(admin.getByText('No events yet.')).toBeVisible();
});

test('an edit made here is the title everybody reads', async ({ as, page }) => {
    const admin = await as('admin');

    await openList(admin);
    await row(admin).getByRole('button', { name: 'Edit' }).click();

    const dialog = admin.getByRole('dialog');

    await expect(dialog.getByLabel('Event title')).toHaveValue(TITLE);
    await dialog.getByLabel('Event title').fill('E2E Admin Event, renamed');
    await dialog.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByText('Event updated!').first()).toBeVisible();
    await expect(row(admin, 'E2E Admin Event, renamed')).toBeVisible();

    await page.goto(`/events/${eventId('E2E Admin Event, renamed')}`);
    await hydrated(page);
    await expect(page.getByRole('heading', { name: 'E2E Admin Event, renamed' })).toBeVisible();
});

test('pausing from the admin list shows on the event, and resuming undoes it', async ({ as, page }) => {
    const admin = await as('admin');

    await openList(admin);
    await row(admin).getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Pause event' }).click();

    await expect(row(admin)).toContainText('Paused');

    await page.goto(`/events/${eventId(TITLE)}`);
    await hydrated(page);
    await expect(page.getByText('Paused').first()).toBeVisible();

    // Reopened, the way a person would to undo it.
    await admin.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();
    await row(admin).getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Resume event' }).click();

    await expect(row(admin)).not.toContainText('Paused');
});

test('the status tab follows a pause without being reopened', async ({ as }) => {
    const admin = await as('admin');

    await openList(admin);
    await row(admin).getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('dialog').getByRole('tab', { name: 'Status' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Pause event' }).click();

    await expect(admin.getByRole('dialog').getByRole('button', { name: 'Resume event' })).toBeVisible({ timeout: 3000 });
});

test('saving a new title from the admin list leaves the bingo card as it was', async ({ as }) => {
    const admin = await as('admin');

    await openList(admin);
    await row(admin).getByRole('button', { name: 'Edit' }).click();
    await admin.getByRole('dialog').getByLabel('Event title').fill('E2E Admin Event, renamed');
    await admin.getByRole('dialog').getByRole('button', { name: 'Save', exact: true }).click();
    await expect(admin.getByText('Event updated!').first()).toBeVisible();

    const card = query("SELECT size, win_condition, requires_approval FROM bingo_cards WHERE event_id IN (SELECT id FROM events WHERE title LIKE 'E2E Admin Event%')")[0];

    expect(card).toMatchObject({ size: 3, win_condition: 'FULL_HOUSE', requires_approval: 0 });
});

test('a deleted event disappears for visitors, stays findable here marked as deleted, and comes back when restored', async ({ as, page, watcher }) => {
    watcher.allow(404, /\/events\//);

    const admin = await as('admin');
    const id = eventId(TITLE);

    await openList(admin);
    await row(admin).getByRole('button', { name: 'Delete' }).click();

    // Marked, with the way back beside it — the only place that exists.
    await expect(row(admin)).toContainText('Deleted');
    await expect(row(admin).getByRole('button', { name: 'Restore' })).toBeVisible();
    await expect(row(admin).getByRole('button', { name: 'Edit' })).toHaveCount(0);

    expect((await page.goto(`/events/${id}`)).status()).toBe(404);

    // Out of the active list, in the deleted one.
    await pickStatus(admin, 'Active');
    await expect(row(admin)).toHaveCount(0);
    await pickStatus(admin, 'Deleted');
    await expect(row(admin)).toBeVisible();

    await row(admin).getByRole('button', { name: 'Restore' }).click();
    await expect(row(admin)).toHaveCount(0);

    expect((await page.goto(`/events/${id}`)).status()).toBe(200);
});

test('the status filter tells paused from running', async ({ as }) => {
    const admin = await as('admin');

    run("UPDATE events SET paused_at = datetime('now') WHERE title = ?", [TITLE]);

    await openList(admin);
    await pickStatus(admin, 'Paused');
    await expect(row(admin)).toBeVisible();

    await pickStatus(admin, 'Active');
    await expect(row(admin)).toBeVisible();
});
