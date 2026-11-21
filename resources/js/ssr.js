import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue } from 'laravel-vue-i18n';
import ui from '@nuxt/ui/vue-plugin';
import AppRoot from './AppRoot.vue';

// This is the process under evaluation: does @nuxt/ui survive
// `renderToString` in Inertia's Node SSR server? tv-dashboard (the sibling
// prototype this config is modeled on) never runs this file — it's
// client-rendered only. If a component here throws or the Ziggy `route()`
// global is unavailable server-side, this is where it will surface.

// Inertia v2 puts the page's renderToString() result straight into a wrapper
// div's innerHTML without awaiting it. Vue 3.5.44+ stringifies innerHTML, so
// the page rendered as "[object Promise]". The page is rendered here on its
// own and spliced into the wrapper once both are done. Inertia v3 awaits it.
const PAGE_SLOT = '<!--inertia-page-->';

function createRenderer() {
    let pageApp = null;
    let pageHtml = null;

    return {
        track: (app) => (pageApp = app),
        render: (app) => {
            if (app === pageApp) {
                pageHtml = renderToString(app);
                return PAGE_SLOT;
            }

            return renderToString(app).then(async (shell) => {
                const html = pageHtml ? await pageHtml : '';
                return shell.replace(PAGE_SLOT, () => html);
            });
        },
    };
}

// Several sites share one server, so each SSR process listens on the port of
// its own site's INERTIA_SSR_URL instead of Inertia's default 13714.
function ssrPort() {
    let url = process.env.INERTIA_SSR_URL;

    if (!url) {
        try {
            const env = readFileSync(resolve(dirname(fileURLToPath(import.meta.url)), '../../.env'), 'utf8');
            url = env.match(/^INERTIA_SSR_URL=["']?([^"'\s#]+)/m)?.[1];
        } catch (error) {
            console.error(error);
        }
    }

    const port = url ? Number(new URL(url).port) : 0;

    return port || 13714;
}

createServer((page) => {
    const renderer = createRenderer();

    return createInertiaApp({
        page,
        render: renderer.render,
        // No title template here either — see app.js/Composables/useSeo.js.
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ App, props, plugin }) {
            return renderer.track(createSSRApp({ render: () => h(AppRoot, { page: App, pageProps: props }) })
                .use(plugin)
                .use(ui)
                .use(ZiggyVue, page.props.ziggy)
                // eager, not the Promise-returning glob app.js uses — the
                // package's own SSR guidance: Node's dynamic import() inside
                // the resolve callback would otherwise race the synchronous
                // renderToString() call this file makes.
                .use(i18nVue, {
                    lang: 'en',
                    resolve: (lang) => {
                        const langs = import.meta.glob('../../lang/*.json', { eager: true });
                        return langs[`../../lang/${lang}.json`].default;
                    },
                }));
        },
    });
}, { port: ssrPort() });
