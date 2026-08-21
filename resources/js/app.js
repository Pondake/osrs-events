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
        // Referenced, not resolved. Inertia interpolates this straight into
        // `background`, `box-shadow` and `border-top-color`, all of which take
        // a var() — so the bar follows the theme live through a dark-mode
        // toggle, and cannot drift the way a pinned hex had (this was still
        // the scaffold's violet long after the theme became amber). Reading
        // the computed value here instead would also be too early: the
        // stylesheet is not guaranteed applied at module-eval time, and the
        // snapshot silently came back empty.
        //
        // `--ui-primary` is the brand FILL, which is what a progress bar is —
        // the amber-700 override in app.css applies to brand TEXT, where
        // contrast against a light background matters.
        color: 'var(--ui-primary)',
    },
});
