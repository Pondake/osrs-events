import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import ui from '@nuxt/ui/vite';
import i18n from 'laravel-vue-i18n/vite';
import { uiConfig } from './ui.config';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        i18n(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Mirrors I:\tv-dashboard\vite.config.js — same @nuxt/ui/vite plugin,
        // same Inertia router mode. The only prototype-specific addition is
        // colorMode staying on (needed to prove it survives SSR without a
        // `window`, see the SSR entry / evaluation notes).
        ui({
            router: 'inertia',
            colorMode: true,
            ui: uiConfig,
            autoImport: {
                vueTemplate: true,
                imports: [
                    'vue',
                    {
                        '@inertiajs/vue3': ['router', 'useForm', 'usePage', 'Head'],
                    },
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'ziggy-js': fileURLToPath(new URL('./vendor/tightenco/ziggy', import.meta.url)),
        },
    },
    // NOTE: no ssr.noExternal here — see resources/js/Components/ClientOnly.vue
    // for why. @nuxt/ui components that touch useComponentIcons.js
    // (u-select/u-switch/u-modal/u-tabs — interactive form components, not
    // the static/marketing ones the app first shipped with) statically
    // import a virtual '#imports' specifier that only resolves through the
    // ui() plugin's bundler-time pipeline. Vite's SSR build externalizes
    // node_modules deps by default, bypassing that pipeline, so '#imports'
    // reaches Node unresolved and crashes SSR at import time — UNLESS those
    // components are kept out of the SSR module graph entirely. Forcing
    // @nuxt/ui to bundle instead (ssr.noExternal) does dodge the crash, but
    // trades it for a worse, silent one: every page's SSR output becomes an
    // empty <div id="app">, no error anywhere, because @nuxt/ui's own
    // BUILD-TIME code (dist/vite.mjs, meant to run inside Vite's plugin
    // pipeline, not inside the SSR runtime) gets bundled alongside the
    // runtime components and breaks Vue's component resolution globally.
    // ClientOnly.vue sidesteps the whole problem instead of fighting the
    // bundler over it.
});
