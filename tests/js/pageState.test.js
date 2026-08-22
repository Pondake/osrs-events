import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h, provide } from 'vue';
import { mount } from '@vue/test-utils';

import { CURRENT_PAGE, useCurrentPage } from '@/Support/pageState';

/**
 * Reading the page from above the page.
 *
 * `usePage()` reads a module-scoped store that Inertia's own App component
 * fills in during ITS setup. AppRoot wraps App, so the header and footer —
 * rendered as siblings of the page, not inside it — have their setup run
 * first. On the server, where the app is rebuilt per request but the store is
 * module state that survives between them, that means they read the PREVIOUS
 * request's page.
 *
 * It was not academic: the header's server-side nav is built from
 * `isAuthenticated`, so a signed-out visitor's HTML arrived carrying the
 * previous visitor's menu — links to /events and /teams on a page that was
 * meant to show none of them. Found by alternating a signed-in and a
 * signed-out request against one URL.
 *
 * The fix is a provided value that AppRoot fills from `initialPage`. These
 * tests pin the two halves of that: provided wins, and absent it still works.
 */

const SHARED = { component: 'Stale/Page', props: { auth: { user: { id: 'from-the-store' } } } };

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => SHARED,
}));

/** Reads through the composable and renders what it found. */
const Reader = defineComponent({
    setup() {
        const page = useCurrentPage();

        return () => h('div', `${page.value.component}/${page.value.props.auth.user?.id ?? 'none'}`);
    },
});

describe('useCurrentPage', () => {
    it('prefers what an ancestor provided over the shared store', () => {
        const wrapper = mount(defineComponent({
            setup() {
                provide(CURRENT_PAGE, { value: { component: 'Home', props: { auth: { user: null } } } });

                return () => h(Reader);
            },
        }));

        expect(wrapper.text()).toBe('Home/none');
    });

    /**
     * Page components sit BELOW Inertia's App, where the store is already
     * correct — and anything mounted outside AppRoot's tree has no provider
     * at all. Both have to keep working.
     */
    it('falls back to the shared store when nothing was provided', () => {
        expect(mount(Reader).text()).toBe('Stale/Page/from-the-store');
    });

    /** A provider that has not resolved yet must not blank the page out. */
    it('falls back when the provided value is empty', () => {
        const wrapper = mount(defineComponent({
            setup() {
                provide(CURRENT_PAGE, { value: null });

                return () => h(Reader);
            },
        }));

        expect(wrapper.text()).toBe('Stale/Page/from-the-store');
    });
});
