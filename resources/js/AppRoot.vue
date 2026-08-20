<template>
    <u-app>
        <app-header />

        <!-- Site-wide announcement, set in admin site settings. Rendered
             above the page rather than inside it so it shows everywhere,
             and server-side (no client-only) so it's in the served HTML. -->
        <div v-if="announcement" class="border-b border-default" :class="bannerClass">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-start gap-2 text-sm">
                <u-icon :name="bannerStyle.icon" class="size-4 shrink-0 mt-0.5" :class="bannerIconClass" />
                <p class="text-highlighted"><announcement-text :text="announcement" /></p>
            </div>
        </div>

        <component :is="page" v-bind="pageProps" />
        <app-footer />

        <!-- Lives here rather than on any one page because it has to be able
             to appear wherever a new user first lands. ClientOnly for the
             same reason the header's interactive bits are — u-modal pulls in
             the '#imports' virtual specifier that breaks the SSR build (see
             the useToast note below). -->
        <client-only>
            <onboarding-modal v-if="showOnboarding" v-model:open="showOnboarding" />
        </client-only>
    </u-app>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
import AnnouncementText from '@/Components/AnnouncementText.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import { styleFor } from '@/Support/announcement';

const OnboardingModal = defineAsyncComponent(() => import('@/Components/OnboardingModal.vue'));

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

// Local ref seeded from the shared prop rather than bound straight to it:
// the modal writes to this on close, and the server prop only flips after
// /onboarding/complete round-trips. Without the local copy the modal would
// stay open until that response landed.
const announcement = computed(() => inertiaPage.props?.site?.announcement ?? null);
const bannerStyle = computed(() => styleFor(inertiaPage.props?.site?.announcementType));

// Written out per colour rather than built as `bg-${color}/10`: Tailwind
// scans source text for class names, so an interpolated one is never
// generated and the banner would render with no background at all.
const BANNER_BG = {
    primary: 'bg-primary/10',
    success: 'bg-success/10',
    warning: 'bg-warning/10',
    error: 'bg-error/10',
};
const BANNER_ICON = {
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
};
const bannerClass = computed(() => BANNER_BG[bannerStyle.value.color]);
const bannerIconClass = computed(() => BANNER_ICON[bannerStyle.value.color]);

const showOnboarding = ref(false);
const needsOnboarding = computed(() => inertiaPage.props?.auth?.user?.needsOnboarding ?? false);

onMounted(() => {
    showOnboarding.value = needsOnboarding.value;
});

watch(needsOnboarding, (needs) => {
    if (needs) showOnboarding.value = true;
});
</script>
