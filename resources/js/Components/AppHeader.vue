<template>
    <u-header>
        <!-- u-header already wraps this slot's content in its own ULink
             pointed at its `to` prop (default "/", left at that default) —
             confirmed by reading Header.vue's source after this crashed the
             entire SSR Node process. A raw <a href="/"> here nests <a>
             inside u-header's own <a>, which threw
             "Cannot read properties of undefined (reading 'startsWith')"
             deep in Nuxt UI's own Link active-route-detection logic during
             SSR — not a graceful per-request failure, an uncaught exception
             that killed the whole long-running SSR process. Plain content,
             no link, is what the slot actually wants. -->
        <template #title>
            <span class="text-lg font-bold text-highlighted">⚔️ OSRS Events</span>
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
import { useAuth } from '@/Composables/useAuth';
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
                  label: 'Boards',
                  icon: 'i-lucide-layout-grid',
                  children: [
                      { label: 'Boards', to: '/boards', icon: 'i-lucide-layout-grid', description: 'Browse and play boards' },
                      { label: 'Manage boards', to: '/admin/boards', icon: 'i-lucide-settings', description: 'Every board, including unlisted ones' },
                  ],
              }
            : { label: 'Boards', to: '/boards', icon: 'i-lucide-layout-grid' },
    );

    if (isAdmin.value || isTeamManager.value) {
        items.push({ label: 'Teams', to: '/teams', icon: 'i-lucide-users' });
    }

    if (isAdmin.value || isEditor.value) {
        items.push({ label: 'Tasks', to: '/admin/tasks', icon: 'i-lucide-list-checks' });
    }

    if (isAdmin.value) {
        items.push({ label: 'Users', to: '/admin/users', icon: 'i-lucide-user-cog' });
    }

    return items;
});
</script>
