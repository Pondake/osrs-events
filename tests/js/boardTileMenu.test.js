import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { mount } from '@vue/test-utils';
import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';

const BoardTileMenu = (await import('@/Components/BoardTileMenu.vue')).default;

/**
 * The right-click menu for a board's tiles.
 *
 * Almost all of its design is about what it must NOT do, which is why it is
 * worth a test at all: it wraps the grid, and the grid is the page. It may
 * not add an element (the connectors overlay measures that box), and it may
 * not reach the server's render graph at all — `u-context-menu` pulls in
 * `#imports`, which only resolves through the `ui()` Vite plugin and crashes
 * the SSR process at startup (docs/ssr-gotchas.md #6).
 *
 * The stub stands in for @nuxt/ui's component the way it actually behaves:
 * the default slot becomes the trigger, with no wrapper of its own.
 */
const ContextMenuStub = {
    props: ['items', 'disabled', 'ui'],
    setup: (_, { slots }) => () => slots.default?.(),
};

function mountMenu(props = {}) {
    return mount(BoardTileMenu, {
        props: { items: [], ...props },
        slots: { default: '<div class="grid" data-testid="grid">tiles</div>' },
        global: { stubs: { 'u-context-menu': ContextMenuStub } },
    });
}

describe('BoardTileMenu', () => {
    /**
     * The grid it wraps has to come out the other side as itself. An extra
     * element here would sit between the tiles and the absolutely positioned
     * connectors overlay, which measures the grid's own box to draw snakes
     * and ladders across it.
     */
    it('adds no element of its own around the grid', () => {
        const wrapper = mountMenu();

        // Compared whole rather than by looking for the grid: "the grid is
        // still in there somewhere" would pass with a wrapper around it,
        // which is the failure this is about. The comment in the template
        // renders as a node too, and is not an element.
        const rendered = wrapper.html().replace(/<!--[\s\S]*?-->/g, '').trim();

        expect(rendered).toBe('<div class="grid" data-testid="grid">tiles</div>');
    });

    it('hands the menu its items', () => {
        const items = [[{ label: 'Edit this tile' }], [{ label: 'Show tile details' }]];
        const wrapper = mountMenu({ items });

        expect(wrapper.findComponent(ContextMenuStub).props('items')).toEqual(items);
    });

    /**
     * Disabled is not a detail: it is how a right-click on the gap between
     * two tiles gives the browser's own menu back. On a plain background,
     * copy/inspect/reload is the more useful menu, and a menu that opens
     * everywhere with nothing in it is worse than none.
     */
    it('is disabled by default, and only enabled when told', () => {
        expect(mountMenu().findComponent(ContextMenuStub).props('disabled')).toBe(false);
        expect(mountMenu({ disabled: true }).findComponent(ContextMenuStub).props('disabled')).toBe(true);
    });

    /**
     * The server pass has to produce the grid, unchanged. This is the half
     * `<ClientOnly>` could not do — it renders nothing on the server, and a
     * board that only exists after hydration is a board a crawler never sees
     * and a reader waits for.
     */
    it('renders its slot on the server', async () => {
        const app = createSSRApp({
            render: () => h(BoardTileMenu, { items: [] }, {
                default: () => h('div', { class: 'grid' }, 'tiles'),
            }),
        });

        app.component('u-context-menu', ContextMenuStub);

        const html = await renderToString(app);

        expect(html).toContain('class="grid"');
        expect(html).toContain('tiles');
    });
});

/**
 * The rule the component itself cannot enforce, checked against the source.
 *
 * BoardTileMenu is only safe because BoardShow never imports it statically —
 * a plain `import` at the top of the page would put `u-context-menu` in the
 * server bundle and take the SSR process down at startup, which is a failure
 * that looks like the whole site being offline rather than like one menu
 * being wrong. It is one word's difference and no test that renders anything
 * would catch it, so it is checked where it is written.
 */
describe('BoardShow — how it reaches the menu', () => {
    const source = readFileSync(resolve(__dirname, '../../resources/js/Pages/BoardShow.vue'), 'utf8');

    it('imports the menu dynamically and never statically', () => {
        expect(source).toMatch(/import\(\s*['"]@\/Components\/BoardTileMenu\.vue['"]\s*\)/);
        expect(source).not.toMatch(/^\s*import\s+\w+\s+from\s+['"]@\/Components\/BoardTileMenu\.vue['"]/m);
    });

    /**
     * And the swap happens after mount, which is the only moment that is
     * definitely not the server.
     */
    it('swaps the wrapper in from onMounted', () => {
        expect(source).toMatch(/onMounted\(\(\)\s*=>\s*\{\s*tileMenuWrapper\.value\s*=/);
    });
});
