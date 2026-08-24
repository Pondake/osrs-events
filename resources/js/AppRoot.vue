<template>
    <u-app>
        <!-- The torch-lit background, on the pages people read rather than
             the ones they work in. One fixed layer behind everything (see
             .landing-chrome in app.css) — no wrapper, no effect on layout,
             and it cannot take a click. -->
        <div v-if="isLanding" class="landing-chrome" aria-hidden="true" />

        <app-header v-if="showSiteChrome" />

        <!-- Site-wide announcement, set in admin site settings. Rendered
             above the page rather than inside it so it shows everywhere,
             and server-side (no client-only) so it's in the served HTML. -->
        <div v-if="announcement && showSiteChrome && !announcementDismissed" class="border-b border-default" :class="bannerClass">
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
                <div class="flex items-start gap-2">
                    <p class="flex-1 text-sm text-center text-highlighted max-w-3xl mx-auto">
                        <u-icon :name="bannerStyle.icon" class="size-4 inline-block align-[-3px] me-1.5" :class="bannerIconClass" />
                        <rich-text :text="announcement" />
                    </p>

                    <!-- Dismissable, and remembered per announcement rather
                         than per visit. It sits on every page including the
                         admin area, so for the person who runs the site it
                         was the most repeated sentence in the app. Keyed by
                         the text itself: editing the announcement makes it a
                         new one, which is the only reliable signal that the
                         message has changed and deserves to be seen again. -->
                    <u-button
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        :aria-label="$t('common.dismiss')"
                        @click="dismissAnnouncement"
                    />
                </div>
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

        <!-- The in-app way in.

             The automatic ask only reliably raises a prompt on Chromium.
             Firefox has needed a user gesture since 72 and ignores the call
             otherwise, Safari the same, and Chrome may answer with its quiet
             UI — a bell in the address bar that looks exactly like nothing
             having happened. Clicking this is a real gesture, so it produces
             a real prompt everywhere, and on iOS it is the only route there
             has ever been.

             Not rendered server-side: every input is browser state that does
             not exist during SSR, and a bar that appears and then vanishes on
             hydration is worse than one that arrives a moment late. -->
        <client-only>
            <div v-if="showPushOffer" class="border-b border-default bg-primary/10">
                <div class="w-full max-w-(--ui-container) mx-auto px-4 sm:px-6 lg:px-8 py-2">
                    <div class="flex items-center justify-center gap-x-3 gap-y-1 flex-wrap text-sm text-center">
                        <span class="text-highlighted">
                            <u-icon name="i-lucide-bell" class="size-4 inline-block align-[-3px] me-1.5 text-primary" />
                            {{ $t('notifications.offer_line') }}
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <u-button
                                size="xs"
                                color="primary"
                                variant="soft"
                                :loading="push.busy.value"
                                :label="$t('notifications.offer_enable')"
                                @click="acceptPushOffer"
                            />
                            <u-button
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :label="$t('notifications.offer_later')"
                                @click="dismissPushOffer"
                            />
                        </span>
                    </div>
                </div>
            </div>
        </client-only>

        <!-- display:contents, so the wrapper is a hook for the panel
             styling and nothing else — it generates no box and the page
             lays out exactly as it did without it. -->
        <div :class="isLanding ? 'contents landing-page' : 'contents'">
            <component :is="page" v-bind="pageProps" />
        </div>
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
import { computed, defineAsyncComponent, onMounted, provide, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
import RichText from '@/Components/RichText.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import { styleFor } from '@/Support/announcement';
import { isLandingPage } from '@/Support/landing';
import { CURRENT_PAGE } from '@/Support/pageState';
import { usePush } from '@/Composables/usePush';
// Aliased: this file already has its own snooze pair for the onboarding
// tour, and the two are unrelated windows over unrelated questions.
import {
    shouldOfferPush,
    snooze as snoozePushOffer,
    snoozedUntil as pushOfferSnoozedUntil,
} from '@/Support/pushPrompt';

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
const rootProps = defineProps({
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

const sharedPage = usePage();

/**
 * The page being rendered — this request's, not the last one's.
 *
 * `usePage()` reads a module-scoped store that Inertia's own App component
 * fills in during ITS setup. AppRoot wraps App, so AppRoot's setup runs
 * first — and on the server, where the app is built fresh per request but the
 * store is module state that survives between them, that means AppRoot reads
 * the PREVIOUS request's page.
 *
 * Measured rather than assumed: requesting `/` and `/events` alternately, the
 * server-rendered chrome lagged exactly one request behind every time. Only
 * what AppRoot itself renders server-side is affected — nothing user-specific
 * is server-rendered (the header's menus are all client-only), so this was a
 * wrong announcement or a missing background rather than one visitor's page
 * served to another. Checked that too.
 *
 * `initialPage` is handed to setup() per request and is always this one. It
 * is also correct on the client's first render, which keeps hydration
 * matching; after that the store is authoritative, because AppRoot persists
 * across client-side visits while initialPage never changes again.
 */
const hydrated = ref(false);

onMounted(() => {
    hydrated.value = true;
});

const inertiaPage = computed(() => (hydrated.value ? sharedPage : rootProps.pageProps?.initialPage ?? sharedPage));

// Handed down to everything AppRoot renders beside the page — the header and
// footer most of all, whose server-side markup is built from `isAuthenticated`
// and so was carrying the previous visitor's nav. See Support/pageState.js.
provide(CURRENT_PAGE, inertiaPage);

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
    raise(inertiaPage.value.props?.flash?.boardSave, 'board-save', 'success');
    raise(inertiaPage.value.props?.flash?.boardSaveError, 'board-save-error', 'error');
});

/**
 * Every successful Inertia visit, rather than a watcher on the flash value.
 *
 * A watcher only fires when the value *changes*, and a flash message is very
 * often identical to the one before it: approving a queue of bingo claims
 * flashes "Claim approved" every time, so the first approval toasted and none
 * of the rest did. Reported from staging, and the same shape of miss made the
 * Wise Old Man lookup look broken when it was answering fine.
 *
 * Reading from the event's own page payload rather than from `inertiaPage`
 * keeps the earlier fix intact too: props is briefly undefined mid-visit while
 * Inertia swaps page state, and a getter reading through it threw for real
 * (clicking "Roll dice" — "Cannot read properties of undefined").
 *
 * Does not fire on the initial document load, which is what the onMounted
 * raise() above is for.
 */
router.on('success', (event) => {
    const flash = event.detail?.page?.props?.flash;

    raise(flash?.boardSave, 'board-save', 'success');
    raise(flash?.boardSaveError, 'board-save-error', 'error');
});

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

const isLanding = computed(() => isLandingPage(inertiaPage.value.component));

const showSiteChrome = computed(() => {
    const component = String(inertiaPage.value.component ?? '');

    if (component.startsWith('Admin/') || CHROMELESS_PAGES.includes(component)) return false;

    return !(inertiaPage.value.props?.site?.locked && AUTH_PAGES.includes(component));
});

// Pages that are themselves asking the user for something — the OSRS username
// gate above all. A brand-new Discord account needs onboarding AND has no
// username, so both flows want the screen at once; the tour used to win and
// opened on top of the gate, while its own endpoints sit behind that same gate
// (so "Skip for now" and the join-a-board step bounced off it). The gate wins,
// and the unverified-name notice stays quiet here too — it would be nagging
// about the very field on screen.
const onAuthPage = computed(() => String(inertiaPage.value.component ?? '').startsWith('Auth/'));

const announcement = computed(() => inertiaPage.value.props?.site?.announcement ?? null);

/**
 * Which announcement this browser has already read.
 *
 * localStorage rather than a database column: it is a per-person, per-device
 * preference about one sentence, and syncing it would mean a write on every
 * dismissal for something nobody will ever ask "why is this not on my other
 * laptop" about. Read after mount so the server-rendered HTML always
 * contains the banner — hiding it during SSR would mean a hydration mismatch
 * on the one element that is meant to be seen.
 */
const DISMISS_KEY = 'announcement-dismissed';
const dismissedText = ref(null);

onMounted(() => {
    try {
        dismissedText.value = window.localStorage.getItem(DISMISS_KEY);
    } catch (error) {
        // Private mode, or storage disabled. The banner simply stays.
        console.error(error);
    }
});

const announcementDismissed = computed(() => announcement.value !== null && dismissedText.value === announcement.value);

function dismissAnnouncement() {
    dismissedText.value = announcement.value;

    try {
        window.localStorage.setItem(DISMISS_KEY, announcement.value);
    } catch (error) {
        console.error(error);
    }
}
const bannerStyle = computed(() => styleFor(inertiaPage.value.props?.site?.announcementType));

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
const osrsUsername = computed(() => inertiaPage.value.props?.auth?.user?.osrsUsername ?? null);
const showOsrsNotice = computed(
    () => showSiteChrome.value
        && ! onAuthPage.value
        && !! osrsUsername.value
        && inertiaPage.value.props?.auth?.user?.osrsVerified === false,
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
    () => (inertiaPage.value.props?.auth?.user?.needsOnboarding ?? false) && ! onAuthPage.value,
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
/**
 * Push notifications — registration, the silent opt-in, and the tap.
 *
 * Lives here rather than on the settings page because both halves have to
 * happen on every page: a subscription the server has lost heals itself on
 * whatever page the user happens to open, and a notification tapped from the
 * lock screen can land anywhere in the app.
 */
const push = usePush();

onMounted(async () => {
    // Registered for signed-out visitors too. It costs one request, and a
    // registered worker is part of what makes the app installable — which on
    // iOS is a precondition for notifications existing at all, long before
    // anybody logs in.
    await push.refresh();

    if (! inertiaPage.value.props?.auth?.user) return;

    // Seeded from the shared auth props so the very first autoSubscribe knows
    // about an opt-out. Without it, somebody who switched notifications off
    // would be silently resubscribed on their next page load — the OS
    // permission is still granted, which is exactly what "subscribe silently"
    // keys on.
    push.hydrate({
        optedOut: inertiaPage.value.props?.auth?.user?.pushOptedOut,
        serverConfigured: inertiaPage.value.props?.site?.pushConfigured,
    });

    await push.autoSubscribe();

    // Read after the automatic attempt, not before: the attempt may change
    // the permission, and the offer below is only for the case where it did
    // not.
    pushSnoozedUntil.value = pushOfferSnoozedUntil(window.localStorage);
});

/**
 * Whether to offer the bar. The decision itself lives in Support/pushPrompt so
 * the eight-way answer is testable without a browser.
 */
const pushSnoozedUntil = ref(0);
const pushOfferDismissed = ref(false);

const showPushOffer = computed(() => {
    if (pushOfferDismissed.value) return false;

    return shouldOfferPush({
        signedIn: !! inertiaPage.value.props?.auth?.user,
        supported: push.supported.value,
        configured: inertiaPage.value.props?.site?.pushConfigured !== false,
        permission: push.permission.value,
        optedOut: push.optedOut.value,
        isIos: push.isIos(),
        isStandalone: push.isStandalone(),
        snoozedUntil: pushSnoozedUntil.value,
        now: Date.now(),
        settled: push.settled.value,
        // Something actually on screen asking for something — the auth pages
        // and the tour while it is *open*. Deliberately not `needsOnboarding`,
        // which stays true until the tour is finished: somebody who closes it
        // (it snoozes for a day) would otherwise never see this bar at all.
        // That is what hid it from a fresh account on staging.
        onBlockingPage: onAuthPage.value || showOnboarding.value,
        // The same gate the announcement banner and the OSRS notice use, for
        // the same reason: those pages have nowhere to put a bar.
        hasChrome: showSiteChrome.value,
    });
});

async function acceptPushOffer() {
    // Clears the once-ever memory first: the automatic attempt sets that flag
    // *before* calling, so a prompt the browser silently refused to show has
    // already spent it. Without this the explicit click could inherit that
    // and look just as broken.
    push.clearPromptMemory();

    const granted = await push.enable();

    // Either way the bar has done its job. A refusal is an answer, and
    // repeating the question is what turns a prompt into a reason to leave.
    pushOfferDismissed.value = true;

    if (! granted) snoozePushOffer(window.localStorage);
}

function dismissPushOffer() {
    pushOfferDismissed.value = true;
    snoozePushOffer(window.localStorage);
}

/**
 * The service worker's fallback route for a tapped notification.
 *
 * `client.navigate()` is unavailable on some platforms and rejects for
 * clients the worker does not control, so it posts here instead. Routing
 * client-side is also faster than the reload navigate() would have caused.
 *
 * The path is validated before it is used. This listener will accept a
 * message from any future source, and a value that is not a same-origin path
 * turns router.push into an open redirect.
 */
onMounted(() => {
    if (typeof navigator === 'undefined' || ! ('serviceWorker' in navigator)) return;

    navigator.serviceWorker.addEventListener('message', (event) => {
        const { type, path } = event.data ?? {};

        if (type !== 'app:navigate') return;
        // Leading single slash only: `//evil.example` is a protocol-relative
        // URL that the browser reads as another origin entirely.
        if (typeof path !== 'string' || ! path.startsWith('/') || path.startsWith('//')) return;

        router.visit(path);
    });
});
</script>
