import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, eventId, resetEvent } from './support/db.js';
import { clearGains, setGains } from './support/wom.js';

/**
 * A drop race from both ends: the people who enter it and the host who asks
 * Wise Old Man how they are doing. There is nothing to claim and nothing to
 * review — the standings are the whole event, so what matters is that they
 * are honest: a tie shares a rank, somebody Wise Old Man has never heard of is
 * listed as an entrant rather than ranked last with a zero.
 *
 * The numbers come from the stand-in in serve.js, which each test sets.
 */
const RACE = 'E2E Drop Race';

test.beforeEach(() => {
    clearThrottles();
    clearGains();
    resetEvent(RACE);
});

test.afterAll(() => clearGains());

async function open(page) {
    await page.goto(`/events/${eventId(RACE)}`);
    await hydrated(page);
}

const enter = (page) => page.getByRole('button', { name: 'Enter the race' }).first();
// The standings list, not every listitem: a toast that names a player is one too.
const rows = (page) => page.locator('ul.divide-y > li');
const row = (page, name) => rows(page).filter({ hasText: name });

async function join(page) {
    await open(page);
    await enter(page).click();
    await expect(page.getByRole('button', { name: 'Leave the race' })).toBeVisible();
}

test('an empty race says so, and a visitor is offered to enter', async ({ page }) => {
    await open(page);

    await expect(page.getByText('No standings yet')).toBeVisible();
    await expect(enter(page)).toBeVisible();
});

test('entering puts you in the standings, unmeasured until Wise Old Man knows the name, and leaving takes you out', async ({ as }) => {
    const player = await as('member');

    await join(player);

    // Entering reads the name once; nobody has given the stand-in a number for it.
    await expect(row(player, 'E2E Member')).toContainText('Not tracked');
    await expect(player.getByText('No standings yet')).toHaveCount(0);

    await player.getByRole('button', { name: 'Leave the race' }).click();

    const confirm = player.getByRole('dialog').getByRole('button', { name: /leave/i });

    if (await confirm.isVisible().catch(() => false)) await confirm.click();

    await expect(enter(player)).toBeVisible();
    await expect(player.getByText('No standings yet')).toBeVisible();
});

test('the host\'s sync ranks the entrants, ties share a rank, and an unknown name is not ranked', async ({ as }) => {
    setGains({
        'E2E Member': { vorkath: 12 },
        'E2E Creator': { vorkath: 7 },
        'E2E Unreachable': { vorkath: 7 },
    });

    for (const seat of ['member', 'creator', 'unreachable', 'cohost']) await join(await as(seat));

    const host = await as('owner');

    await open(host);
    await host.getByRole('button', { name: 'Update from Wise Old Man' }).first().click();

    // Three of the four came back; the toast names the one that did not.
    await expect(host.getByText('Updated 3 of 4. No data came back for E2E Cohost').first()).toBeVisible();

    await expect(row(host, 'E2E Member')).toHaveText(/^1\D.*\+12/);
    await expect(row(host, 'E2E Creator')).toHaveText(/^2\D.*\+7/);
    await expect(row(host, 'E2E Unreachable')).toHaveText(/^2\D.*\+7/);
    await expect(row(host, 'E2E Cohost')).toHaveText(/^—.*Not tracked/);

    // The best comes first, and the one nobody could measure comes last.
    const names = await rows(host).locator('p.font-medium').allTextContents();

    expect(names[0]).toBe('E2E Member');
    expect(names.at(-1)).toBe('E2E Cohost');
});

test('the standings follow the numbers: a later sync can change the order', async ({ as }) => {
    setGains({ 'E2E Member': { vorkath: 3 }, 'E2E Creator': { vorkath: 9 } });

    for (const seat of ['member', 'creator']) await join(await as(seat));

    const host = await as('owner');

    await open(host);
    await host.getByRole('button', { name: 'Update from Wise Old Man' }).first().click();
    await expect(host.getByText('Standings updated').first()).toBeVisible();
    await expect(row(host, 'E2E Creator')).toHaveText(/^1\D/);

    setGains({ 'E2E Member': { vorkath: 30 }, 'E2E Creator': { vorkath: 9 } });

    await host.getByRole('button', { name: 'Update from Wise Old Man' }).first().click();
    await expect.poll(async () => (await row(host, 'E2E Member').innerText()).trim()).toMatch(/^1\D/);
    await expect(row(host, 'E2E Creator')).toHaveText(/^2\D/);

    // Anybody can read it, and only the host is offered the sync.
    const reader = await as('admin');

    await open(reader);
    await expect(row(reader, 'E2E Member')).toHaveText(/^1\D.*\+30/);
    await expect(reader.getByRole('button', { name: 'Update from Wise Old Man' })).toHaveCount(0);
});

test('a race has no board, so its leaderboard address leads to the event page', async ({ page }) => {
    await page.goto(`/events/${eventId(RACE)}/leaderboard`);

    await expect(page).toHaveURL(new RegExp(`/events/${eventId(RACE)}$`));
});
