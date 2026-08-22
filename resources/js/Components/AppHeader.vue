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
    <!-- #left, not #title: the #title slot renders INSIDE u-header's own
         ULink (see the block above for why that link has to stay defused),
         which left the wordmark unclickable. #left replaces that wrapper
         entirely, so a plain <a href="/"> restores the usual "logo goes
         home" without going near the Link override that crashes.
         `to=""` stays as a guard — if anyone drops this slot, the default
         ULink comes back and needs it.
         It also fixes a real a11y bug: ULink derived its aria-label from
         the slot's text content, and swept an HTML comment that lived in
         there into it, so screen readers announced a paragraph about
         Tailwind sizing as the header's label. Hence the explicit
         aria-label here, and the note about osrs-game-font — which pins its
         own 24px size in app.css, so a Tailwind text-* class on the wordmark
         would fight it and land the pixel face on a fractional size. -->
    <u-header to="">
        <template #left>
            <a href="/" class="flex items-center gap-2 text-highlighted" :aria-label="$t('common.app_name')">
                <app-logo />
                <span class="osrs-game-font">{{ $t('common.app_name') }}</span>
            </a>
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
                        v-if="item.to && !item.children"
                        :to="item.to"
                        :icon="item.icon"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        :label="item.label"
                    />

                    <!-- u-popover with mode="hover", not u-dropdown-menu.
                         A dropdown menu is a click target by definition:
                         clicking "Events" opened a list instead of going to
                         Events, so the top-level destination was reachable
                         from the footer and the logo and nowhere in the nav
                         that is named after it. A hover popover leaves the
                         trigger a plain link — click navigates, hover
                         reveals the rest.

                         The delays matter more than they look. 0ms open
                         means the panel flashes at anything the pointer
                         crosses on its way elsewhere, and 0ms close means it
                         vanishes while you are moving diagonally into it.
                         300ms open is hover-INTENT rather than hover: the
                         trigger is also a link, and a panel that appears the
                         instant the pointer arrives fights the click you were
                         already on your way to make.

                         z-[60] because the panel is portalled to <body> with
                         z-index:auto while the header is a sticky z-50
                         stacking context — so the header painted over its own
                         dropdown. Reported as "the submenu falls under the
                         header".

                         Touch is left out (`enable-touch` defaults false):
                         a tap on a touch screen fires hover AND click, so a
                         panel would open just as the page navigates away.
                         Touch gets the mobile drawer below instead. -->
                    <u-popover v-else-if="item.children" mode="hover" :open-delay="300" :close-delay="200" :ui="{ content: 'p-1 w-56 z-[60]' }">
                        <u-button
                            :to="item.to"
                            :icon="item.icon"
                            trailing-icon="i-lucide-chevron-down"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            :label="item.label"
                        />

                        <template #content>
                            <ul class="flex flex-col">
                                <li v-for="child in item.children" :key="child.label">
                                    <!-- A planned entry has no destination,
                                         so it is a <span>, not a dead <a>.
                                         `disabled` alone only dims a row,
                                         which reads as broken rather than
                                         as not-built-yet — hence the badge
                                         saying which it is. -->
                                    <component
                                        :is="child.to ? 'a' : 'span'"
                                        :href="child.to"
                                        class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm"
                                        :class="child.to ? 'text-default hover:bg-elevated transition-colors' : 'text-dimmed cursor-default'"
                                    >
                                        <u-icon v-if="child.icon" :name="child.icon" class="size-4 shrink-0" />
                                        <span class="truncate">{{ child.label }}</span>
                                        <u-badge
                                            v-if="child.soon"
                                            :label="$t('nav.badge_soon')"
                                            color="neutral"
                                            variant="subtle"
                                            size="sm"
                                            class="ml-auto shrink-0"
                                        />
                                    </component>
                                </li>
                            </ul>
                        </template>
                    </u-popover>
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
                        <!-- A group now carries its own `to` as well, so the
                             parent link is emitted here too rather than only
                             its children. -->
                        <!-- `v-if="child.to"` skips the not-yet entries:
                             they have no destination, and emitting a bare
                             <a> without href would put a dead link in front
                             of a crawler. -->
                        <template v-for="child in item.children ?? []" :key="child.label">
                            <a
                                v-if="child.to"
                                :href="child.to"
                                class="text-sm text-muted hover:text-primary transition-colors"
                            >{{ child.label }}</a>
                        </template>
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

        <!-- The mobile drawer. Same `navigation` array as the desktop bar, so
             the two can't drift — but it needs its own trailing slot: the
             desktop bar renders through u-dropdown-menu and this through
             u-navigation-menu, and neither passes the other's slots. Without
             this, planned entries showed up dimmed on mobile with nothing
             saying why. -->
        <template v-if="navigation.length" #body>
            <!-- Hand-rolled rather than u-navigation-menu, for the same
                 reason the desktop bar dropped u-dropdown-menu: a group with
                 children turned its whole row into an accordion toggle, so
                 the parent destination had no way to be tapped. Here the row
                 is the link and the chevron beside it is its own button with
                 its own hit area — two targets, which is what a group that
                 is both a place and a list needs on touch. -->
            <nav class="p-4 flex flex-col gap-1">
                <template v-for="item in navigation" :key="item.label">
                    <div class="flex items-center gap-1">
                        <component
                            :is="item.to ? 'a' : 'button'"
                            :href="item.to"
                            :type="item.to ? undefined : 'button'"
                            class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-md text-sm font-medium text-default hover:bg-elevated transition-colors text-left"
                            @click="!item.to && toggleGroup(item.label)"
                        >
                            <u-icon v-if="item.icon" :name="item.icon" class="size-4 shrink-0" />
                            <span>{{ item.label }}</span>
                        </component>

                        <u-button
                            v-if="item.children"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            square
                            :icon="openGroups.has(item.label) ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                            :aria-expanded="openGroups.has(item.label)"
                            :aria-label="$t(openGroups.has(item.label) ? 'nav.collapse_group' : 'nav.expand_group', { name: item.label })"
                            @click="toggleGroup(item.label)"
                        />
                    </div>

                    <ul v-if="item.children && openGroups.has(item.label)" class="flex flex-col gap-0.5 pl-5 mb-1">
                        <li v-for="child in item.children" :key="child.label">
                            <component
                                :is="child.to ? 'a' : 'span'"
                                :href="child.to"
                                class="flex items-center gap-2 px-3 py-2 rounded-md text-sm"
                                :class="child.to ? 'text-muted hover:bg-elevated hover:text-default transition-colors' : 'text-dimmed cursor-default'"
                            >
                                <u-icon v-if="child.icon" :name="child.icon" class="size-4 shrink-0" />
                                <span class="truncate">{{ child.label }}</span>
                                <u-badge
                                    v-if="child.soon"
                                    :label="$t('nav.badge_soon')"
                                    color="neutral"
                                    variant="subtle"
                                    size="sm"
                                    class="ml-auto shrink-0"
                                />
                            </component>
                        </li>
                    </ul>
                </template>
            </nav>
        </template>
    </u-header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import AppLogo from '@/Components/AppLogo.vue';
import { isPublicPath } from '@/Support/landing';
import UserMenu from '@/Components/UserMenu.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const { isAuthenticated, isAdmin } = useAuth();

// Shared by HandleInertiaRequests, and it already means "the door is shut for
// THIS visitor" — false for an admin and false for anyone who has typed the
// shared password. Re-checking isAdmin here would only get the second of
// those wrong.
const page = usePage();
const locked = computed(() => Boolean(page.props?.site?.locked));

// Which groups are expanded in the mobile drawer. A Set rather than a single
// value so opening one does not close another — the drawer is a list you
// scan, not a wizard.
const openGroups = ref(new Set());

function toggleGroup(label) {
    // Reassigned, not mutated: Vue's reactivity tracks Set methods on a
    // `reactive` Set, but this is a `ref` holding a plain one, and mutating
    // it in place would not re-render.
    const next = new Set(openGroups.value);

    next.has(label) ? next.delete(label) : next.add(label);
    openGroups.value = next;
}

// The guide pages are the site's SEO surface. They used to be reachable
// only from the footer, which is the weakest internal-linking position on a
// page — a header entry is what actually signals they matter, so they're
// here for logged-out visitors (the ones a crawler renders as) and stay
// visible logged in, since they're genuinely useful to players too.
// No `description` on any of these on purpose: u-navigation-menu lays a
// dropdown's children out side by side and sizes the panel to its trigger,
// so descriptions turn every label into a one-word-per-line column. These
// labels say enough on their own.
/**
 * Marks a planned destination: shown so the menu reads as a finished
 * product, but visibly not-yet and deliberately not a link.
 *
 * `disabled` keeps it out of the tab order and unclickable, so nothing here
 * can 404 — these pages genuinely don't exist yet. Revisit each one when the
 * feature lands rather than leaving them to rot: a "soon" that never arrives
 * is worse than not listing it at all. Tracked in docs/backlog.md.
 */
const soon = (item) => ({ ...item, disabled: true, soon: true });

const guideChildren = () => [
    { label: trans('nav.snakes'), to: '/osrs-snakes-and-ladders', icon: 'i-lucide-arrow-up-from-line' },
    { label: trans('nav.clan_events'), to: '/osrs-clan-events', icon: 'i-lucide-users' },
    { label: trans('nav.event_ideas'), to: '/osrs-event-ideas', icon: 'i-lucide-lightbulb' },
    soon({ label: trans('nav.runelite'), icon: 'i-lucide-puzzle' }),
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
 * Admin entries stay out of both — they live under /admin,
 * reachable from the settings sidebar and the user menu. This nav is for
 * playing, not administering.
 */
/**
 * While the site is locked, everything that is not a public page bounces
 * straight back to the lock screen — so a nav offering those links is a menu
 * of dead ends on the one page a stranger is meant to reach.
 *
 * Trimmed rather than hidden: the guides and About are open (see
 * EnsureSiteUnlocked), and they are the whole reason the public pages were
 * let through in the first place.
 */
const navigation = computed(() => {
    if (locked.value) {
        return [
            { label: trans('nav.guides'), icon: 'i-lucide-book-open', children: guideChildren().filter((c) => isPublicPath(c.to)) },
            { label: trans('nav.about'), to: '/about', icon: 'i-lucide-info' },
        ];
    }

    if (!isAuthenticated.value) {
        return [
            { label: trans('nav.boards'), to: '/events', icon: 'i-lucide-layout-grid' },
            { label: trans('nav.guides'), icon: 'i-lucide-book-open', children: guideChildren() },
            { label: trans('nav.about'), to: '/about', icon: 'i-lucide-info' },
        ];
    }

    const items = [
        {
            label: trans('nav.boards'),
            icon: 'i-lucide-layout-grid',
            // A group with a destination of its own: clicking goes to the
            // events index, hovering (desktop) or the chevron (mobile)
            // opens the rest. Reported as "I still cannot click Events" —
            // the item had children and no `to`, so the only thing a click
            // could do was open a list.
            to: '/events',
            children: [
                { label: trans('nav.my_boards'), to: '/my-events', icon: 'i-lucide-gamepad-2' },
                { label: trans('nav.browse_boards'), to: '/events', icon: 'i-lucide-compass' },
                soon({ label: trans('nav.calendar'), icon: 'i-lucide-calendar-days' }),
            ],
        },
        {
            label: trans('nav.community'),
            icon: 'i-lucide-users-round',
            children: [
                // Every signed-in account gets Teams. It used to be gated on
                // isAdmin || isTeamManager, which was wrong in both
                // directions: creating a team needs no permission at all
                // (TeamController::store), and the page is now scoped to the
                // teams you are actually in or share a Discord server with —
                // so an ordinary player has both a reason to open it and
                // something to see there.
                { label: trans('nav.teams'), to: '/teams', icon: 'i-lucide-users' },
                soon({ label: trans('nav.leaderboards'), icon: 'i-lucide-trophy' }),
                soon({ label: trans('nav.clans'), icon: 'i-lucide-shield' }),
            ],
        },
        { label: trans('nav.guides'), icon: 'i-lucide-book-open', children: guideChildren() },
    ];

    return items;
});
</script>
