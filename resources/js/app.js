import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue } from 'laravel-vue-i18n';
import ui from '@nuxt/ui/vue-plugin';
import AppRoot from './AppRoot.vue';

createInertiaApp({
    // No title template here — see Composables/useSeo.js for why the suffix
    // is applied exactly once, in the page-level composable, instead of here.
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(AppRoot, { page: App, pageProps: props }) })
            .use(plugin)
            .use(ui)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                },
            })
            .mount(el);
    },
    progress: {
        color: '#7c3aed',
    },
});
