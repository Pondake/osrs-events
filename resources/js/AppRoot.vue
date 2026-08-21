<template>
    <u-app>
        <app-header v-if="showSiteChrome" />

        <!-- Site-wide announcement, set in admin site settings. Rendered
             above the page rather than inside it so it shows everywhere,
             and server-side (no client-only) so it's in the served HTML. -->
        <div v-if="announcement && showSiteChrome" class="border-b border-default" :class="bannerClass">
            <!-- Same container as u-header and every page body: Nuxt UI's own
                 --ui-container token with its padding scale. It previously used
                 max-w-7xl + px-4 and so sat 8px left of the header wordmark and
                 the page heading, drifting further at sm/lg where the others
                 step up to 24/32px. Matching the token makes them line up by
                 construction instead of by a guess that happens to agree. -->
            <div class="w-full max-w-(--ui-container) mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <!-- The icon sits INSIDE the paragraph, not beside it in a flex
                     row. As a sibling it anchored to the text block's left edge,
                     which on any width where the copy fills the line meant it
                     drifted away from the words it belongs to. Inline, it is the
                     first thing on the first line at every width, and wraps with
                     the sentence. -->
                <p class="text-sm text-center text-highlighted max-w-3xl mx-auto">
                    <u-icon :name="bannerStyle.icon" class="size-4 inline-block align-[-3px] me-1.5" :class="bannerIconClass" />
                    <rich-text :text="announcement" />
                </p>
            </div>
        </div>

        <!-- Recurring on purpose. An account Wise Old Man can't find is one
             that will silently score nothing in every race it enters, and the
             only person who can fix that is the owner of the name — so this
             comes back rather than being dismissed once and forgotten. It is
             a notice, never a block: their API only knows accounts somebody
             has already looked up there, so a real newcomer legitimately
             isn't found. -->
        <div v-if="showOsrsNotice" class="border-b border-default bg-warning/10">
            <div class="w-full max-w-(--ui-container) mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <div class="flex items-center justify-center gap-x-3 gap-y-1 flex-wrap text-sm text-center">
                    <span class="text-highlighted">
                        <u-icon name="i-lucide-triangle-alert" class="size-4 inline-block align-[-3px] me-1.5 text-warning" />
                        {{ $t('auth.osrs_unverified_banner', { name: osrsUsername }) }}
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <u-button
                            size="xs"
                            color="warning"
                            variant="soft"
                            :loading="rechecking"
                            :label="$t('auth.osrs_recheck')"
                            @click="recheckOsrs"
                        />
                        <u-button
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            href="/settings/profile"
                            :label="$t('auth.osrs_fix_name')"
                        />
                    </span>
                </div>
            </div>
        </div>

        <component :is="page" v-bind="pageProps" />
        <app-footer v-if="showSiteChrome" />

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
import { router, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
import RichText from '@/Components/RichText.vue';
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

const inertiaPage = usePage();

function raise(message, id, color) {
    if (message) toast?.add({ id, title: message, color });
}

onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();

    // The flash carried by THIS page load, which the watchers below cannot
    // see: a watcher without `immediate` only fires on a change, and adding
    // `immediate` would not help either — it runs during setup, before the
    // dynamic import above has resolved `toast`.
    //
    // Only reachable on a full page load, since an Inertia visit changes the
    // value and the watchers handle it — but a full page load is exactly how
    // every redirect from outside the app arrives. The Discord OAuth
    // callback is the one that made this matter: a cancelled or expired
    // login redirects to /login with an explanation, and the explanation was
    // silently dropped, leaving the user bounced to a login page for no
    // stated reason.
    raise(inertiaPage.props?.flash?.boardSave, 'board-save', 'success');
    raise(inertiaPage.props?.flash?.boardSaveError, 'board-save-error', 'error');
});

// Optional-chained on `props` itself, not just `flash` — props is briefly
// undefined mid-visit while Inertia swaps page state for the new response,
// and these watchers can fire in that window (confirmed live: clicking
// "Roll dice" threw "Cannot read properties of undefined (reading 'flash')"
// from exactly these two getters).
watch(
    () => inertiaPage.props?.flash?.boardSave,
    (message) => raise(message, 'board-save', 'success'),
);

watch(
    () => inertiaPage.props?.flash?.boardSaveError,
    (message) => raise(message, 'board-save-error', 'error'),
);

/**
 * Pages that render without the site header, footer and announcement banner.
 *
 * Two of them, for opposite reasons:
 *
 *  - **Admin/** brings its own full-height shell (AdminLayout's dashboard
 *    sidebar + navbar), so the site chrome would sit on top of it rather
 *    than around it.
 *  - **SiteLock** is a closed door. It rendered the full header — nav links,
 *    the user menu, and whatever banner happened to be up — which is exactly
 *    what a pre-launch lock is meant to keep off the screen. The links all
 *    bounced back here anyway, so the chrome was a menu of dead ends
 *    wrapped around a password box.
 *
 * Keyed on the Inertia component name because that is the one thing AppRoot
 * reliably knows about the page it is rendering — it has no access to the
 * page component's own options.
 */
const CHROMELESS_PAGES = ['SiteLock'];

/**
 * The auth pages, which keep the chrome normally and lose it while the site
 * is locked.
 *
 * The lock screen offers "Running this site? Log in" as the other way in —
 * and that click used to land on a fully dressed page with the nav, the user
 * menu and whatever banner was up. Every one of those links bounces straight
 * back to the lock screen, so it was a menu of dead ends wrapped around a
 * password box, on the one page a stranger is meant to reach.
 */
const AUTH_PAGES = ['Auth/Login', 'Auth/Register', 'Auth/ForgotPassword', 'Auth/ResetPassword'];

const showSiteChrome = computed(() => {
    const component = String(inertiaPage.component ?? '');

    if (component.startsWith('Admin/') || CHROMELESS_PAGES.includes(component)) return false;

    return !(inertiaPage.props?.site?.locked && AUTH_PAGES.includes(component));
});

// Pages that are themselves asking the user for something — the OSRS username
// gate above all. A brand-new Discord account needs onboarding AND has no
// username, so both flows want the screen at once; the tour used to win and
// opened on top of the gate, while its own endpoints sit behind that same gate
// (so "Skip for now" and the join-a-board step bounced off it). The gate wins,
// and the unverified-name notice stays quiet here too — it would be nagging
// about the very field on screen.
const onAuthPage = computed(() => String(inertiaPage.component ?? '').startsWith('Auth/'));

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

// Local ref seeded from the shared prop rather than bound straight to it:
// the modal writes to this on close, and the server prop only flips after
// /onboarding/complete round-trips. Without the local copy the modal would
// stay open until that response landed.
// Shown whenever the signed-in account has a name we have never managed to
// confirm. Not dismissible: the consequence of ignoring it is scoring nothing
// in every race, and a one-click dismiss makes that permanent and silent.
// Hidden on the gate page itself, where the user is already being asked.
const osrsUsername = computed(() => inertiaPage.props?.auth?.user?.osrsUsername ?? null);
const showOsrsNotice = computed(
    () => showSiteChrome.value
        && ! onAuthPage.value
        && !! osrsUsername.value
        && inertiaPage.props?.auth?.user?.osrsVerified === false,
);

const rechecking = ref(false);

function recheckOsrs() {
    rechecking.value = true;
    router.post('/settings/profile/osrs/verify', {}, {
        preserveScroll: true,
        onFinish: () => (rechecking.value = false),
        onError: (errors) => console.error(errors),
    });
}

const showOnboarding = ref(false);

const needsOnboarding = computed(
    () => (inertiaPage.props?.auth?.user?.needsOnboarding ?? false) && ! onAuthPage.value,
);

/**
 * Closing the tour any way other than the buttons — Escape, the X, a click
 * outside — used to persist nothing at all, so it reopened on the very next
 * page load, forever. "Skip" writes onboarding_completed_at and is final;
 * this is the middle ground that was missing: gone for a day, then offered
 * again.
 *
 * localStorage rather than sessionStorage because a day has to survive
 * closing the tab, and rather than a cookie because the server has no use
 * for it — it would ride along on every single request for nothing.
 */
const SNOOZE_KEY = 'onboarding-snoozed-until';
const SNOOZE_MS = 24 * 60 * 60 * 1000;

function snoozedUntil() {
    if (typeof window === 'undefined') return 0;

    try {
        return Number(window.localStorage.getItem(SNOOZE_KEY)) || 0;
    } catch (error) {
        // Private mode and blocked storage both throw on access rather than
        // returning null. Treating that as "not snoozed" shows the tour,
        // which is the harmless direction to fail in.
        console.error(error);

        return 0;
    }
}

function snoozeOnboarding() {
    try {
        window.localStorage.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_MS));
    } catch (error) {
        console.error(error);
    }
}

onMounted(() => {
    showOnboarding.value = needsOnboarding.value && snoozedUntil() < Date.now();
});

// Fires for every close, including the ones that reach `finish()` — harmless
// there, since that account is already flagged complete server-side and the
// stale key simply expires.
watch(showOnboarding, (open, wasOpen) => {
    if (wasOpen && ! open && needsOnboarding.value) {
        snoozeOnboarding();
    }
});

watch(needsOnboarding, (needs) => {
    // Snooze checked here too, not just on mount: this fires on navigation
    // (the gate page clearing, for one), and without it the tour would
    // reopen on the next page change no matter what was stored.
    if (needs && snoozedUntil() < Date.now()) {
        showOnboarding.value = true;
    } else if (onAuthPage.value) {
        // Close only when a blocking page is the reason. Onboarding finishing
        // also flips this false, and that case is the modal's own to handle.
        showOnboarding.value = false;
    }
});
</script>
