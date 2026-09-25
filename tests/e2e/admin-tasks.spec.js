import { test, expect, hydrated } from './fixtures.js';
import { clearThrottles, query, removeE2eTasks } from './support/db.js';

/**
 * The task library an event's tiles and squares are picked from: making one,
 * changing it, and taking it away and getting it back. Deleting is the part
 * that is easy to get wrong — it is a soft delete with a way back, and a
 * button that says deleted while the row is still there is a lie.
 *
 * Every task is named "E2E …" and removed afterwards, trashed ones too.
 */
test.beforeEach(() => {
    clearThrottles();
    removeE2eTasks();
});

test.afterEach(() => removeE2eTasks());

const row = (page, title) => page.locator('.divide-y > div').filter({ hasText: title });

async function openTasks(admin) {
    await admin.goto('/admin/tasks');
    await hydrated(admin);
}

async function createTask(admin, title, description = '') {
    await admin.getByRole('button', { name: 'New task' }).click();

    const dialog = admin.getByRole('dialog');

    await dialog.getByLabel('Title').fill(title);
    if (description) await dialog.getByLabel('Description').fill(description);
    await dialog.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByText('Task created!').first()).toBeVisible();
    await expect(dialog).toBeHidden();
    await expect(row(admin, title)).toBeVisible();
}

test('a new task is in the list, and stays there after a reload', async ({ as }) => {
    const admin = await as('admin');

    await openTasks(admin);
    await createTask(admin, 'E2E Kill Vorkath', 'Any kill counts.');

    await expect(row(admin, 'E2E Kill Vorkath')).toContainText('Any kill counts.');

    await admin.reload();
    await hydrated(admin);
    await expect(row(admin, 'E2E Kill Vorkath')).toBeVisible();
});

test('a task needs a title', async ({ as }) => {
    const admin = await as('admin', { console: [/./] });

    await openTasks(admin);
    await admin.getByRole('button', { name: 'New task' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByRole('dialog')).toBeVisible();
    expect(query("SELECT COUNT(*) AS n FROM tasks WHERE title = ''")[0].n).toBe(0);
});

test('an edit replaces the title and description', async ({ as }) => {
    const admin = await as('admin');

    await openTasks(admin);
    await createTask(admin, 'E2E Kill Zulrah');

    await row(admin, 'E2E Kill Zulrah').getByRole('button', { name: 'Edit' }).click();

    const dialog = admin.getByRole('dialog');

    await expect(dialog.getByLabel('Title')).toHaveValue('E2E Kill Zulrah');
    await dialog.getByLabel('Title').fill('E2E Kill Zulrah twice');
    await dialog.getByLabel('Description').fill('Two kills in one trip.');
    await dialog.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(admin.getByText('Task updated!').first()).toBeVisible();
    await expect(row(admin, 'E2E Kill Zulrah twice')).toContainText('Two kills in one trip.');
    await expect(row(admin, 'E2E Kill Zulrah').filter({ hasNotText: 'twice' })).toHaveCount(0);
});

test('a deleted task leaves the list and can be undone', async ({ as }) => {
    const admin = await as('admin');

    await openTasks(admin);
    await createTask(admin, 'E2E Kill Callisto');

    await row(admin, 'E2E Kill Callisto').getByRole('button', { name: 'Delete' }).click();
    await admin.getByRole('button', { name: 'Delete', exact: true }).last().click();

    await expect(admin.getByText('Task deleted.').first()).toBeVisible();
    await expect(row(admin, 'E2E Kill Callisto')).toHaveCount(0);

    // Soft: the row is still there, marked, until somebody restores it or it is purged.
    expect(query("SELECT deleted_at FROM tasks WHERE title = 'E2E Kill Callisto'")[0].deleted_at).not.toBeNull();

    await admin.getByRole('button', { name: 'Undo' }).click();

    await expect(row(admin, 'E2E Kill Callisto')).toBeVisible();
    expect(query("SELECT deleted_at FROM tasks WHERE title = 'E2E Kill Callisto'")[0].deleted_at).toBeNull();
});

test('undoing a delete says the task is restored', async ({ as }) => {
    const admin = await as('admin');

    await openTasks(admin);
    await createTask(admin, 'E2E Kill Kraken');

    await row(admin, 'E2E Kill Kraken').getByRole('button', { name: 'Delete' }).click();
    await admin.getByRole('button', { name: 'Delete', exact: true }).last().click();
    await admin.getByRole('button', { name: 'Undo' }).click();

    await expect(admin.getByText('Task restored.').first()).toBeVisible({ timeout: 3000 });
});

test('the search and the wiki filter narrow the list, and say so when nothing is left', async ({ as }) => {
    const admin = await as('admin');

    await openTasks(admin);
    await createTask(admin, 'E2E Alpha task');
    await createTask(admin, 'E2E Beta task');

    await admin.getByPlaceholder('Search tasks').fill('Beta');
    await expect(row(admin, 'E2E Beta task')).toBeVisible();
    await expect(row(admin, 'E2E Alpha task')).toHaveCount(0);

    await admin.getByPlaceholder('Search tasks').fill('');
    await admin.getByRole('combobox').click();
    await admin.getByRole('option', { name: 'With wiki link' }).click();

    await expect(row(admin, 'E2E Alpha task')).toHaveCount(0);
    await expect(admin.getByText(/no task|every task/i).first()).toBeVisible();
});
