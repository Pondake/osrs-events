import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue, loadLanguageAsync } from 'laravel-vue-i18n';
import ui from '@nuxt/ui/vue-plugin';
import AppRoot from './AppRoot.vue';

createInertiaApp({
    // No title template here — see Composables/useSeo.js for why the suffix
    // is applied exactly once, in the page-level composable, instead of here.
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(AppRoot, { page: App, pageProps: props }) })
            .use(plugin)
            .use(ui)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                },
            });

        // i18nVue's `shared: true` default means this awaits the SAME
        // instance the plugin above just registered — loadLanguageAsync()
        // (module-level export) reads I18n.getSharedInstance(). Without
        // this await, mount() below runs while the client's language file
        // is still an in-flight dynamic import, so any trans() call at
        // <script setup> top level (not inside a template's reactive $t())
        // resolves before messages exist and permanently caches the raw
        // key — confirmed live: the Home page's browser tab title showed
        // literally "seo.home_title - OSRS Events" after hydration,
        // despite the server-rendered HTML having the correct title,
        // because useSeoData's `resolved` computed captured trans()'s
        // fallback value once and never re-evaluated it. SSR doesn't hit
        // this at all — the I18n constructor uses a synchronous
        // `loadLanguage` server-side, only the client path is async.
        await loadLanguageAsync('en');

        app.mount(el);
    },
    progress: {
        color: '#7c3aed',
    },
});
