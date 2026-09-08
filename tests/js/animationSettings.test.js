import { describe, expect, it, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createSSRApp } from 'vue';
import { renderToString } from '@vue/server-renderer';

// Inertia's <Head> reaches for a provider that only exists inside a real
// Inertia app, and router.put() would fire a request. Neither is what this
// file is about.
vi.mock('@inertiajs/vue3', () => ({
    Head: { render: () => null },
    router: { put: vi.fn() },
}));

const Animations = (await import('@/Pages/Settings/Animations.vue')).default;

/**
 * The reduced-motion notice on the animation settings page.
 *
 * Driven from a browser API rather than a prop, which is the whole reason it
 * needs a test: `matchMedia` does not exist during SSR, so the value is read
 * in `onMounted` and the page has two renders — one without the notice and one
 * with. Neither the server nor a feature test can see the second.
 *
 * The bug behind it: an account with reduced motion on saw both switches
 * reading "on" while the board animated nothing at all, because playMove()
 * bailed out on the media query before anything else. Found on a work laptop
 * whose Windows animation effects were off without its owner knowing.
 */
const stubs = {
    SettingsLayout: { template: '<div><slot /></div>' },
    'u-card': { template: '<div><slot name="header" /><slot /></div>' },
    'u-separator': true,
    'u-alert': { props: ['title', 'description'], template: '<div class="alert">{{ title }}</div>' },
    'u-switch': { props: ['modelValue'], template: '<button class="switch" />' },
};

function mountPage({ reduced, overrideOn = false }) {
    window.matchMedia = vi.fn((query) => ({
        matches: query.includes('prefers-reduced-motion') ? reduced : false,
        media: query,
        addEventListener() {},
        removeEventListener() {},
    }));

    return mount(Animations, {
        props: {
            preferences: {
                animate_own_moves: true,
                animate_other_moves: true,
                play_when_reduced_motion: overrideOn,
            },
            keys: ['animate_own_moves', 'animate_other_moves'],
            overrideKey: 'play_when_reduced_motion',
        },
        global: {
            stubs,
            mocks: { $t: (key) => `t:${key}` },
        },
    });
}

describe('Settings/Animations — the reduced-motion notice', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('says nothing on a machine that has no reduced-motion preference', async () => {
        const wrapper = mountPage({ reduced: false });
        await flushPromises();

        expect(wrapper.find('.alert').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('t:animations.play_when_reduced_motion');
    });

    /**
     * The switches are not what is wrong here, so the notice explains rather
     * than blames — but it has to be there, because without it the page shows
     * two controls that are silently not in charge.
     */
    it('explains itself when the system asks for reduced motion', async () => {
        const wrapper = mountPage({ reduced: true });
        await flushPromises();

        expect(wrapper.find('.alert').exists()).toBe(true);
        expect(wrapper.text()).toContain('t:animations.reduced_motion_title');
    });

    it('offers the override only where the question exists', async () => {
        const off = mountPage({ reduced: false });
        await flushPromises();
        expect(off.text()).not.toContain('t:animations.play_when_reduced_motion');

        const on = mountPage({ reduced: true });
        await flushPromises();
        expect(on.text()).toContain('t:animations.play_when_reduced_motion');
    });

    /**
     * Once the override is on there is no conflict left to report — the board
     * will animate. The switch stays, so it can be turned back off.
     */
    it('drops the notice once the override answers it, and keeps the switch', async () => {
        const wrapper = mountPage({ reduced: true, overrideOn: true });
        await flushPromises();

        expect(wrapper.find('.alert').exists()).toBe(false);
        expect(wrapper.text()).toContain('t:animations.play_when_reduced_motion');
    });

    /**
     * The server pass must not consult `matchMedia`, and this is the only
     * check that runs under the real condition: `renderToString` skips
     * `onMounted` exactly the way SSR does, so a component that read the
     * media query during render would throw here on the missing global — and
     * in production would instead paint a notice the client then removes,
     * which is a hydration mismatch rather than an error.
     */
    it('renders on the server without a window, and without the notice', async () => {
        const app = createSSRApp(Animations, {
            preferences: { animate_own_moves: true, animate_other_moves: true, play_when_reduced_motion: false },
            keys: ['animate_own_moves', 'animate_other_moves'],
            overrideKey: 'play_when_reduced_motion',
        });

        app.config.globalProperties.$t = (key) => `t:${key}`;
        Object.entries(stubs).forEach(([name, stub]) => app.component(name, stub === true ? { render: () => null } : stub));

        const html = await renderToString(app);

        expect(html).not.toContain('alert');
        expect(html).not.toContain('t:animations.play_when_reduced_motion');
        expect(html).toContain('t:animations.animate_own_moves');
    });
});
