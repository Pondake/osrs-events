import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import ui from '@nuxt/ui/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
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
});
