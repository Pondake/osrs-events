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
            <u-navigation-menu v-if="navigation.length" :items="navigation" />
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

const { isAuthenticated, isAdmin, isEditor, isTeamManager } = useAuth();

// Ported from the old AppHeader.vue's `navigation` computed — same
// role-based structure (Boards gets an admin sub-menu for editors/admins,
// Teams only shows for admin/team-manager, a top-level Admin menu for
// admins only).
const navigation = computed(() => {
    if (!isAuthenticated.value) return [];

    const items = [];
    const canManageBoards = isAdmin.value || isEditor.value;

    items.push(
        canManageBoards
            ? {
                  label: trans('nav.boards'),
                  icon: 'i-lucide-layout-grid',
                  children: [
                      { label: trans('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid', description: trans('nav.boards_desc') },
                      { label: trans('nav.admin_boards'), to: '/admin/boards', icon: 'i-lucide-settings', description: trans('nav.admin_boards_desc') },
                  ],
              }
            : { label: trans('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid' },
    );

    if (isAdmin.value || isTeamManager.value) {
        items.push({ label: trans('nav.teams'), to: '/teams', icon: 'i-lucide-users' });
    }

    if (isAdmin.value || isEditor.value) {
        items.push({ label: trans('nav.tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks' });
    }

    if (isAdmin.value) {
        items.push({ label: trans('nav.admin_users'), to: '/admin/users', icon: 'i-lucide-user-cog' });
    }

    return items;
});
</script>
