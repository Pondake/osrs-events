import { test, expect, hydrated } from './fixtures.js';
import { resetNotesPage } from './support/db.js';

/**
 * Editing a page and publishing it, from the admin side and from the side of
 * the person reading it. Every edit is only real once a visitor sees it, and
 * a draft is only a draft if a visitor gets nothing.
 *
 * Works on a page of its own (see E2eSeeder) so the legal pages that other
 * specs read are never touched.
 */
test.beforeEach(() => resetNotesPage());
test.afterEach(() => resetNotesPage());

const save = (admin) => admin.getByRole('button', { name: 'Save', exact: true });

async function openEditor(admin) {
    await admin.goto('/admin/content');
    await hydrated(admin);
    await admin.getByRole('link', { name: /E2E Notes/ }).first().click();
    await expect(admin).toHaveURL(/\/admin\/content\/e2e-notes/);
    await hydrated(admin);
}

test('an edit shows up on the live page, and a new paragraph with it', async ({ as, page }) => {
    const admin = await as('admin');

    await openEditor(admin);

    await admin.getByLabel('Page title').fill('E2E Notes, revised');
    await admin.getByRole('button', { name: /Paragraph/ }).first().click();
    await admin.getByLabel('Text', { exact: true }).first().fill('The revised paragraph.');

    await admin.getByRole('button', { name: 'Add block' }).click();
    await admin.getByRole('menuitem', { name: 'Paragraph' }).click();
    await admin.getByLabel('Text', { exact: true }).last().fill('A second paragraph.');

    await save(admin).click();
    await expect(admin.getByText('Page saved!').first()).toBeVisible();

    await page.goto('/e2e-notes');
    await hydrated(page);

    await expect(page.getByRole('heading', { name: 'E2E Notes, revised' })).toBeVisible();
    await expect(page.getByText('The revised paragraph.')).toBeVisible();
    await expect(page.getByText('A second paragraph.')).toBeVisible();
    await expect(page.getByText('The original paragraph.')).toHaveCount(0);
});

test('a draft is gone for visitors and comes back when it is published again', async ({ as, page, watcher }) => {
    const admin = await as('admin');

    watcher.allow(404, /\/e2e-notes/);

    await openEditor(admin);
    await admin.getByRole('switch', { name: /Visibility/ }).click();
    await save(admin).click();
    await expect(admin.getByText('Page saved!').first()).toBeVisible();

    const response = await page.goto('/e2e-notes');

    expect(response.status()).toBe(404);

    await admin.reload();
    await hydrated(admin);
    await admin.getByRole('switch', { name: /Visibility/ }).click();
    await save(admin).click();
    await expect(admin.getByText('Page saved!').first()).toBeVisible();

    const back = await page.goto('/e2e-notes');

    expect(back.status()).toBe(200);
    await expect(page.getByText('The original paragraph.')).toBeVisible();
});

test('a page without a title is refused and nothing changes', async ({ as, page }) => {
    const admin = await as('admin', { console: [/./] });

    await openEditor(admin);
    await admin.getByLabel('Page title').fill('');
    await save(admin).click();

    await expect(admin.getByText(/title field is required/i).first()).toBeVisible();

    await page.goto('/e2e-notes');
    await expect(page.getByRole('heading', { name: 'E2E Notes' })).toBeVisible();
});
