<template>
    <!-- u-header always wraps its #title slot in its own internal ULink
         (default `to="/"`) — not optional, baked into Header.vue itself, so
         a raw <a> or plain <span> in the slot doesn't avoid it, it just nests
         inside it. That ULink's isLinkActive computed reads
         `page.url.startsWith(href.value)` (Link.vue in @nuxt/ui's inertia
         override) on every render. Confirmed live via a Vue dev-mode
         unminified stack trace (`<Link to="/" ... data-slot="title">` inside
         <Header>) throwing "Cannot read properties of undefined (reading
         'startsWith')" on every page during client hydration — page.url
         isn't reliably populated yet at the point AppHeader's title first
         renders, since AppHeader sits before the actual Inertia page
         component in AppRoot.vue's template (same root cause as the
         nav-menu/user-menu crash below, just on a component we don't
         control the internals of). Passing `to=""` makes `href.value` a
         falsy empty string, so isLinkActive's `if (!href.value) return
         false` guard short-circuits before ever reaching .startsWith — the
         title still renders the same span content, still renders
         server-side (unlike ClientOnly-wrapping the whole header would
         require), it just stops being a clickable link to "/". -->
    <u-header to="">
        <template #title>
            <span class="flex items-center gap-2 text-lg font-bold text-highlighted">
                <app-logo />
                {{ $t('common.app_name') }}
            </span>
        </template>

        <!-- u-navigation-menu and any href/to-bound u-button (UserMenu's
             login button, u-color-mode-button) all go through Nuxt UI's
             Inertia-mode Link override, which unconditionally reads
             usePage().url for active-route highlighting. AppHeader renders
             as a SIBLING to, and BEFORE, the actual Inertia page component
             in AppRoot.vue's template — so during SSR, Inertia's own
             page-state hasn't been populated yet at the point these
             evaluate, and `page.url.startsWith(...)` throws on undefined.
             This isn't a per-request failure Inertia catches and falls back
             from — it's an uncaught exception that kills the whole
             long-running SSR Node process outright, confirmed by watching
             it happen live. u-header's own title link (default to="/")
             turned out NOT to hit this in practice even though it goes
             through the same Link code path — not fully explained, but the
             nav menu and login button reproduced it reliably on every
             unauthenticated request, so both are deferred to client-only
             here rather than risking it. -->
        <client-only>
            <!-- Flat links plus a u-dropdown-menu for the one grouped entry,
                 rather than u-navigation-menu's own children support: that
                 sizes its dropdown panel to the trigger's width, so every
                 child label wrapped to one word per line ("My b…" /
                 "Brows…") regardless of min-w overrides. u-dropdown-menu is
                 the same component the user menu already uses and sizes to
                 its content. -->
            <nav v-if="navigation.length" class="hidden lg:flex items-center gap-1">
                <template v-for="item in navigation" :key="item.label">
                    <u-button
                        v-if="item.to"
                        :to="item.to"
                        :icon="item.icon"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        :label="item.label"
                    />
                    <u-dropdown-menu v-else :items="[item.children]">
                        <u-button
                            :icon="item.icon"
                            trailing-icon="i-lucide-chevron-down"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            :label="item.label"
                        />
                    </u-dropdown-menu>
                </template>
            </nav>

            <!-- SSR fallback: the same links as plain <a> markup, so they're
                 in the served HTML for crawlers. Removing the client-only
                 wrapper above and letting u-navigation-menu render on the
                 server was tried and re-confirmed to kill the SSR process
                 outright on the first unauthenticated request (stack ends in
                 Nuxt UI's Button/Link chain, same cause the comment above
                 describes) — so the fallback exists to get the SEO value
                 without that. Flattened one level deep: a crawler only needs
                 the URLs present, not the dropdown behaviour. -->
            <template #fallback>
                <nav class="hidden lg:flex items-center gap-4">
                    <template v-for="item in navigation" :key="item.label">
                        <a v-if="item.to" :href="item.to" class="text-sm text-muted hover:text-primary transition-colors">{{ item.label }}</a>
                        <a
                            v-for="child in item.children ?? []"
                            :key="child.to"
                            :href="child.to"
                            class="text-sm text-muted hover:text-primary transition-colors"
                        >{{ child.label }}</a>
                    </template>
                </nav>
            </template>
        </client-only>

        <template #right>
            <client-only>
                <u-color-mode-button />
                <user-menu />
            </client-only>
        </template>

        <template v-if="navigation.length" #body>
            <div class="p-4">
                <u-navigation-menu :items="navigation" orientation="vertical" />
            </div>
        </template>
    </u-header>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import AppLogo from '@/Components/AppLogo.vue';
import UserMenu from '@/Components/UserMenu.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const { isAuthenticated, isAdmin, isTeamManager } = useAuth();

// The guide pages are the site's SEO surface. They used to be reachable
// only from the footer, which is the weakest internal-linking position on a
// page — a header entry is what actually signals they matter, so they're
// here for logged-out visitors (the ones a crawler renders as) and stay
// visible logged in, since they're genuinely useful to players too.
// No `description` on any of these on purpose: u-navigation-menu lays a
// dropdown's children out side by side and sizes the panel to its trigger,
// so descriptions turn every label into a one-word-per-line column. These
// labels say enough on their own.
const guideChildren = () => [
    { label: trans('nav.snakes'), to: '/osrs-snakes-and-ladders', icon: 'i-lucide-arrow-up-from-line' },
    { label: trans('nav.clan_events'), to: '/osrs-clan-events', icon: 'i-lucide-users' },
    { label: trans('nav.event_ideas'), to: '/osrs-event-ideas', icon: 'i-lucide-lightbulb' },
];

/**
 * Two shapes, because the two audiences want different things:
 *
 * - Logged out (and what a crawler sees): the public board index and the
 *   guides — everything indexable, nothing that needs a session.
 * - Logged in: "My boards" first, since the boards you're actually playing
 *   are the thing you come back for. That page didn't exist before; your
 *   own boards were buried in profile settings.
 *
 * Admin entries stay out of both — they live under /settings/admin,
 * reachable from the settings sidebar and the user menu. This nav is for
 * playing, not administering.
 */
const navigation = computed(() => {
    if (!isAuthenticated.value) {
        return [
            { label: trans('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid' },
            { label: trans('nav.guides'), icon: 'i-lucide-book-open', children: guideChildren() },
            { label: trans('nav.about'), to: '/about', icon: 'i-lucide-info' },
        ];
    }

    const items = [
        {
            label: trans('nav.boards'),
            icon: 'i-lucide-layout-grid',
            children: [
                { label: trans('nav.my_boards'), to: '/my-boards', icon: 'i-lucide-gamepad-2' },
                { label: trans('nav.browse_boards'), to: '/boards', icon: 'i-lucide-compass' },
            ],
        },
    ];

    if (isAdmin.value || isTeamManager.value) {
        items.push({ label: trans('nav.teams'), to: '/teams', icon: 'i-lucide-users' });
    }

    items.push({ label: trans('nav.guides'), icon: 'i-lucide-book-open', children: guideChildren() });

    return items;
});
</script>
