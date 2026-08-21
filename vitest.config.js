import { fileURLToPath, URL } from 'node:url';

import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

/**
 * A separate config from vite.config.js, deliberately.
 *
 * The app's build config loads the Laravel plugin and the Nuxt UI plugin;
 * both want a running Laravel/Vite pipeline and Nuxt UI's own virtual
 * '#imports' module, which is exactly the thing that cannot be resolved
 * outside that pipeline (see CLAUDE.md's SSR gotchas). Pulling it into a
 * test runner drags all of that in for no benefit — these tests cover our
 * own logic, not @nuxt/ui's rendering.
 *
 * So: plain Vue, and components under test are the ones that do not need a
 * Nuxt UI component tree to make sense.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/js/**/*.test.js'],
        // Global setup registers the i18n stub — trans() is used all over
        // Support/ and would otherwise need mocking in every file.
        setupFiles: ['tests/js/setup.js'],
    },
});
