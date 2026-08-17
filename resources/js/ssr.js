import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import ui from '@nuxt/ui/vue-plugin';
import AppRoot from './AppRoot.vue';

// This is the process under evaluation: does @nuxt/ui survive
// `renderToString` in Inertia's Node SSR server? tv-dashboard (the sibling
// prototype this config is modeled on) never runs this file — it's
// client-rendered only. If a component here throws or the Ziggy `route()`
// global is unavailable server-side, this is where it will surface.
createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        // No title template here either — see app.js/Composables/useSeo.js.
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(AppRoot, { page: App, pageProps: props }) })
                .use(plugin)
                .use(ui)
                .use(ZiggyVue, page.props.ziggy);
        },
    }),
);
