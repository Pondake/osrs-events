import { test, expect, hydrated } from './fixtures.js';
import { PAGES, measure } from './support/pages.js';
import { WIDTHS } from './support/seats.js';

/**
 * Every kind of page at every width of the walkthrough, reading the numbers
 * that layout bugs show up in — not judging by eye.
 *
 * 1280, 1024, 768 and 375: the band between tablet and desktop is where the
 * bugs are, and it is the one that gets skipped. The width the browser
 * actually gave is asserted first; a sweep that believes it is at a width it
 * is not proves nothing about that width.
 */
/**
 * The same reading in both themes: dark mode is a second stylesheet's worth of
 * rules, and a width that fits in one can overflow in the other.
 */
for (const scheme of ['light', 'dark']) {
    const label = scheme === 'dark' ? ' (dark)' : '';

    test.describe(scheme, () => {
        test.use({ colorScheme: scheme });

    for (const [group, pages] of Object.entries(PAGES)) {
        for (const width of WIDTHS) {
            test(`${group} pages fit at ${width}px${label}`, async ({ as }) => {
                const page = await as(group === 'anonymous' ? 'unreachable' : group);

                // Anonymous pages are read signed in as well: what a signed-in
                // reader sees is a superset of what a visitor sees, and one seat
                // keeps the run short. Login and register bounce a signed-in
                // account, so those two are read signed out below.
                await page.setViewportSize({ width, height: 800 });

                const problems = [];
                const touch = [];

                for (const path of pages()) {
                    if (group === 'anonymous' && ['/login', '/register', '/forgot-password'].includes(path)) continue;

                    await page.goto(path);
                    await hydrated(page);

                    const result = await page.evaluate(measure);

                    if (result.width !== width) problems.push(`${path}: browser is ${result.width}px wide, not ${width}px`);
                    if (result.dark !== (scheme === 'dark')) problems.push(`${path}: page is ${result.dark ? 'dark' : 'light'}, not ${scheme}`);
                    if (result.overflow !== 0) problems.push(`${path}: overflows by ${result.overflow}px (${result.wide.join(', ')})`);
                    if (width === 375 && result.under44.length > 0) touch.push(`${path}: ${result.under44.join(', ')}`);
                }

                expect(problems).toEqual([]);
                expect(touch, 'touch targets under 44px at 375').toEqual([]);
            });
        }
    }

    for (const width of WIDTHS) {
        test(`the signed-out forms fit at ${width}px${label}`, async ({ page }) => {
            await page.setViewportSize({ width, height: 800 });

            const problems = [];

            for (const path of ['/login', '/register', '/forgot-password']) {
                await page.goto(path);
                await hydrated(page);

                const result = await page.evaluate(measure);

                if (result.width !== width) problems.push(`${path}: browser is ${result.width}px wide, not ${width}px`);
                    if (result.dark !== (scheme === 'dark')) problems.push(`${path}: page is ${result.dark ? 'dark' : 'light'}, not ${scheme}`);
                if (result.overflow !== 0) problems.push(`${path}: overflows by ${result.overflow}px (${result.wide.join(', ')})`);
            }

            expect(problems).toEqual([]);
        });
    }
    });
}
