import { test, expect, hydrated } from './fixtures.js';
import { eventId } from './support/db.js';
import { measureContrast, measureRoles } from './support/contrast.js';

/**
 * Colour, measured in both themes on the page types that matter: every piece
 * of text against the background it is really drawn on, and the design
 * system's colour roles by name. Everything that carries meaning must clear
 * 4.5:1 (3:1 for large text).
 *
 * Eyes are unreliable here — a status colour that looks obviously fine in one
 * theme routinely scores under 2:1 in the other — and a token defined on a
 * bare :root applies in both, so fixing one theme can quietly break the
 * other. Both are read every time. Overflow in dark mode is read by
 * layout.spec.js.
 */
const PAGES = [
    { seat: null, path: () => '/', name: 'landing' },
    { seat: null, path: () => '/events', name: 'events list' },
    { seat: null, path: () => `/events/${eventId('E2E Ladder')}`, name: 'event page' },
    { seat: 'unreachable', path: () => '/settings/profile', name: 'settings' },
    { seat: 'admin', path: () => '/admin/users', name: 'admin' },
];

/**
 * Failures that exist today, keyed by page, element and text, so the suite
 * stays usable while they are open. Not a place to hide things: the assertion
 * is equality, so fixing one fails the test until it is removed from here.
 */
const KNOWN = {
    light: [],
    dark: [],
};

const key = (item) => `${item.by} "${item.text}"`;

const describe = (found) =>
    found.map((item) => `${key(item)}${item.unknown ? ' (over an image or gradient)' : ''}: ${item.ratio}:1, needs ${item.required}:1`);

for (const scheme of ['light', 'dark']) {
    test.describe(scheme, () => {
        test.use({ colorScheme: scheme });

        for (const { seat, path, name } of PAGES) {
            test(`text on the ${name} is readable`, async ({ page, as }) => {
                const reader = seat ? await as(seat) : page;

                await reader.goto(path());
                await hydrated(reader);

                const theme = await reader.evaluate(() => document.documentElement.classList.contains('dark'));

                expect(theme, `the page is ${scheme}`).toBe(scheme === 'dark');

                const found = await reader.evaluate(measureContrast);
                const roles = await reader.evaluate(measureRoles);
                const failing = Object.entries(roles).filter(([, ratio]) => ratio < 4.5).map(([role]) => `role .${role}`);
                const known = KNOWN[scheme].filter((entry) => entry.startsWith(`${name}|`)).map((entry) => entry.slice(name.length + 1));

                test.info().annotations.push({ type: 'measured', description: JSON.stringify(roles) });

                // The keys decide pass or fail; the numbers are only for the report.
                expect([...found.map(key), ...failing].sort(), describe(found).join('; ')).toEqual(known.sort());
            });
        }
    });
}

test('the reading notices text that is too faint, in both themes', async ({ page }) => {
    for (const scheme of ['light', 'dark']) {
        await page.emulateMedia({ colorScheme: scheme });
        await page.goto('/about');
        await hydrated(page);

        // Pale grey on white and dark grey on black: each is 1.3:1 in the theme it is wrong for.
        await page.evaluate(() => {
            const dark = document.documentElement.classList.contains('dark');
            const probe = document.createElement('p');

            probe.textContent = 'faint on purpose';
            probe.style.cssText = `position:fixed;top:0;left:0;z-index:9999;padding:8px;background:${dark ? '#111' : '#fff'};color:${dark ? '#1c1c1c' : '#eee'}`;
            document.body.append(probe);
        });

        const found = await page.evaluate(measureContrast);

        expect(found.map((item) => item.text), scheme).toContain('faint on purpose');
    }
});
