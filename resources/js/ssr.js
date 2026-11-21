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

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
            setup({ App, props, plugin }) {
                return createSSRApp({ render: () => h(AppRoot, { page: App, pageProps: props }) })
                    .use(plugin)
                    .use(ui)
                    .use(ZiggyVue, page.props.ziggy)
                    // Eager glob: a dynamic import() here would resolve after renderToString() has already run.
                    .use(i18nVue, {
                        lang: 'en',
                        resolve: (lang) => {
                            const langs = import.meta.glob('../../lang/*.json', { eager: true });
                            return langs[`../../lang/${lang}.json`].default;
                        },
                    });
            },
        }),
    { port: ssrPort() },
);
