import { test, expect, hydrated } from './fixtures.js';
import { query, resetNewcomer, user } from './support/db.js';

/**
 * The first-run intro, walked the way a new account walks it.
 *
 * The regression that started this suite: the "Account" step offered a link
 * to /settings/account, which loaded that page behind a dialog that cannot be
 * closed without abandoning the intro. The feature tests could not see it
 * because every request in them succeeded. A person saw it in one look.
 */
const EMAILER = "email = 'emailer@e2e.test'";

const dialog = (page) => page.getByRole('dialog');
const activeStep = (page) => dialog(page).locator('[data-slot="item"][data-state="active"] [data-slot="title"]');
const stepTitles = (page) => dialog(page).locator('[data-slot="item"] [data-slot="title"]').allTextContents();

async function openIntro(page) {
    await page.goto('/events');
    await hydrated(page);
    await expect(dialog(page)).toBeVisible();
}

async function next(page) {
    await dialog(page).getByRole('button', { name: 'Next', exact: true }).click();
}

/** Welcome, then the name — the required step in front of the one under test. */
async function reachAccountStep(page, name = 'Pondake') {
    await openIntro(page);
    await next(page);
    await dialog(page).getByLabel('OSRS username').fill(name);
    await next(page);
    await expect(activeStep(page)).toHaveText('Account');
}

test.describe('an account that signed up through Discord', () => {
    test.beforeEach(() => resetNewcomer());

    test('the account step asks for the email in a field, not on another page', async ({ as }) => {
        const page = await as('newcomer');

        await reachAccountStep(page);

        await expect(dialog(page).getByRole('heading', { name: 'Finish setting up your account' })).toBeVisible();
        await expect(dialog(page).getByLabel('Email')).toBeVisible();

        // Whatever else the dialog links to, it must not be a page of this
        // site: the page loads behind it and the dialog stays.
        const links = await dialog(page).locator('a[href]').evaluateAll((anchors) => anchors.map((a) => a.getAttribute('href')));

        expect(links.filter((href) => href.startsWith('/') && !href.startsWith('/settings/account/discord'))).toEqual([]);
    });

    test('an address typed there is saved and the tour carries on to the next step', async ({ as }) => {
        const page = await as('newcomer');

        await reachAccountStep(page);

        const steps = await stepTitles(page);
        const following = steps[steps.indexOf('Account') + 1];

        await dialog(page).getByLabel('Email').fill('newcomer@example.com');
        await next(page);

        // Saving the address removes this step from the list, which moves the
        // next one into its place. Advancing again skipped it.
        await expect(activeStep(page)).toHaveText(following);
        expect(user('e2e_newcomer').email).toBe('newcomer@example.com');
    });

    test('an address that is not one is refused where it was typed', async ({ as }) => {
        const page = await as('newcomer', { console: [/must be a valid email/] });

        await reachAccountStep(page);

        await dialog(page).getByLabel('Email').fill('not an address');
        await next(page);

        await expect(dialog(page).getByText('must be a valid email address')).toBeVisible();
        await expect(activeStep(page)).toHaveText('Account');
        expect(user('e2e_newcomer').email).toBeNull();
    });

    test('an address already on another account is refused', async ({ as }) => {
        const page = await as('newcomer', { console: [/already been taken/] });

        await reachAccountStep(page);

        await dialog(page).getByLabel('Email').fill('member@e2e.test');
        await next(page);

        await expect(dialog(page).getByText(/already been taken/i)).toBeVisible();
        await expect(activeStep(page)).toHaveText('Account');
        expect(user('e2e_newcomer').email).toBeNull();
    });

    test('the step can be walked past empty', async ({ as }) => {
        const page = await as('newcomer');

        await reachAccountStep(page);
        await next(page);

        await expect(activeStep(page)).not.toHaveText('Account');
        expect(user('e2e_newcomer').email).toBeNull();
    });

    test('skipping the intro keeps an address that was typed but not sent', async ({ as }) => {
        const page = await as('newcomer');

        await reachAccountStep(page);

        await dialog(page).getByLabel('Email').fill('kept@example.com');
        await dialog(page).getByText('Skip intro').click();

        await expect(dialog(page)).toBeHidden();
        expect(user('e2e_newcomer').email).toBe('kept@example.com');
        expect(user('e2e_newcomer').onboarding_completed_at).not.toBeNull();
    });

    test('the tour can be walked to its last step, finished, and stays finished', async ({ as }) => {
        const page = await as('newcomer');

        await reachAccountStep(page);

        const last = (await stepTitles(page)).at(-1);

        while ((await activeStep(page).textContent()) !== last) {
            await next(page);
        }

        await dialog(page).getByRole('button', { name: 'Get started', exact: true }).click();
        await expect(dialog(page)).toBeHidden();

        await page.reload();
        await hydrated(page);
        await expect(dialog(page)).toBeHidden();
        expect(user('e2e_newcomer').onboarding_completed_at).not.toBeNull();
    });
});

test.describe('an account that signed up with an email', () => {
    test.beforeEach(() => resetNewcomer({ keepEmail: true, where: EMAILER }));

    test('is not asked for an email it already has', async ({ as }) => {
        const page = await as('emailer');

        await reachAccountStep(page, 'Emailer');

        await expect(dialog(page).getByRole('heading', { name: 'Finish setting up your account' })).toBeVisible();
        await expect(dialog(page).getByLabel('Email')).toHaveCount(0);
        await expect(dialog(page).getByText('Link your Discord account')).toBeVisible();
    });
});

test('the name is required before the tour goes on', async ({ as }) => {
    resetNewcomer();

    const page = await as('newcomer');

    await openIntro(page);
    await next(page);

    await expect(dialog(page).getByRole('button', { name: 'Next', exact: true })).toBeDisabled();
    expect(query('SELECT osrs_username FROM users WHERE discord_username = ?', ['e2e_newcomer'])[0].osrs_username).toBeNull();
});
