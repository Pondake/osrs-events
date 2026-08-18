<template>
    <u-app>
        <component :is="page" v-bind="pageProps" />
    </u-app>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Prop is named `page` (the Vue component to render, per Inertia's
// createInertiaApp setup() contract) — deliberately never captured into a
// same-named local. usePage() below returns Inertia's reactive PAGE STATE
// (url/props/component-name), a completely different thing that happens to
// share the obvious variable name. A `const page = usePage()` here would
// silently shadow the `page` PROP inside this setup scope — and since
// <script setup> exposes declared props to the template implicitly by name,
// the template's `:is="page"` would then resolve to the reactive state
// object instead of the actual component, and silently render nothing.
// Exactly this happened during development: confirmed by curling SSR output
// (empty <div id="app">) and a "Vue received a Component that was made a
// reactive object" warning in the SSR process's own log.
defineProps({
    page: Object,
    pageProps: Object,
});

// Bridges Laravel's session-flash (see HandleInertiaRequests::share()'s
// 'flash' key) to a toast — same stable-id convention as CLAUDE.md's rule
// ("board-save" / "board-save-error" overwrite instead of stacking on
// repeated actions).
//
// useToast — and everything else under '@nuxt/ui/composables', including its
// own barrel — statically imports a virtual '#imports' specifier that only
// resolves through the ui() Vite plugin's bundler-time pipeline. Vite's SSR
// build externalizes node_modules deps by default, bypassing that pipeline,
// so importing useToast at the top of this file (which every page mounts
// through) crashed the entire SSR process at startup regardless of which
// page was being rendered. The dynamic import() inside onMounted() means
// useToast.js is only ever requested client-side, after hydration — never
// during SSR.
let toast;
onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();
});

const inertiaPage = usePage();

// Optional-chained on `props` itself, not just `flash` — props is briefly
// undefined mid-visit while Inertia swaps page state for the new response,
// and these watchers can fire in that window (confirmed live: clicking
// "Roll dice" threw "Cannot read properties of undefined (reading 'flash')"
// from exactly these two getters).
watch(
    () => inertiaPage.props?.flash?.boardSave,
    (message) => {
        if (message) toast?.add({ id: 'board-save', title: message, color: 'success' });
    },
);

watch(
    () => inertiaPage.props?.flash?.boardSaveError,
    (message) => {
        if (message) toast?.add({ id: 'board-save-error', title: message, color: 'error' });
    },
);
</script>
