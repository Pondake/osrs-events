import { computed, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * The page being rendered — this request's, not the last one's.
 *
 * `usePage()` reads a module-scoped store that Inertia's own App component
 * fills in during ITS setup. AppRoot wraps App, so everything AppRoot renders
 * as a SIBLING of the page — the header, the footer, the announcement banner
 * — has its setup run first. On the server, where the app is rebuilt per
 * request but the store is module state that survives between them, those
 * components read the PREVIOUS request's page.
 *
 * That is not academic: the header's server-side nav is built from
 * `isAuthenticated`, so a signed-out visitor's HTML could arrive carrying the
 * previous visitor's menu — links to /events and /teams on a page that was
 * meant to show none. Measured by alternating a signed-in and a signed-out
 * request against the same URL.
 *
 * AppRoot provides the corrected page under this key; anything below it reads
 * through here. The fallback to `usePage()` is for components mounted outside
 * that tree, and for page components themselves, which sit BELOW App and so
 * see a store that is already correct.
 */
export const CURRENT_PAGE = Symbol('current-inertia-page');

export function useCurrentPage() {
    const provided = inject(CURRENT_PAGE, null);
    const shared = usePage();

    return computed(() => provided?.value ?? shared);
}
