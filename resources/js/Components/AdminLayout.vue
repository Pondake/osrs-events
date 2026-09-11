<template>
    <!-- The whole shell is client-only. UDashboardGroup, UDashboardSidebar
         and UDashboardPanel each import the '#imports' virtual specifier,
         and the sidebar additionally calls Nuxt's useRoute() — neither
         resolves outside Nuxt, so rendering these server-side fails the
         page. Admin is behind auth and has no SEO stake, so the trade is
         cheap here in a way it would not be on a public page.

         The #fallback keeps SSR from serving an empty document: it renders
         the same frame (sidebar column + content column) so the layout does
         not jump when the real dashboard hydrates on top of it. -->
    <client-only>
        <u-dashboard-group storage="local" storage-key="osrs-admin-sidebar">
            <u-dashboard-sidebar
                id="admin"
                v-model:open="sidebarOpen"
                collapsible
                resizable
                :min-size="14"
                :default-size="17"
                :max-size="24"
                :ui="{ footer: 'border-t border-default' }"
            >
                <template #header="{ collapsed }">
                    <a href="/" class="flex items-center gap-2 min-w-0" :aria-label="$t('common.app_name')">
                        <app-logo />
                        <span v-if="!collapsed" class="osrs-game-font truncate">{{ $t('common.app_name') }}</span>
                    </a>
                </template>

                <template #default="{ collapsed }">
                    <u-navigation-menu
                        :items="navItems"
                        :collapsed="collapsed"
                        orientation="vertical"
                        tooltip
                    />
                </template>

                <template #footer="{ collapsed }">
                    <!-- Back to the site, not a logout: the admin area is a
                         section of the app, not a separate console. -->
                    <u-button
                        href="/"
                        icon="i-lucide-arrow-left"
                        color="neutral"
                        variant="ghost"
                        :label="collapsed ? undefined : $t('admin.nav_back_to_site')"
                        :square="collapsed"
                        class="w-full"
                        :class="collapsed ? 'justify-center' : 'justify-start'"
                    />
                </template>
            </u-dashboard-sidebar>

            <u-dashboard-panel id="admin-content">
                <template #header>
                    <u-dashboard-navbar :title="title" :ui="{ right: 'gap-2' }">
                        <template #leading>
                            <u-dashboard-sidebar-collapse />
                        </template>

                        <template #right>
                            <u-button
                                v-if="isAdmin"
                                icon="i-lucide-search"
                                color="neutral"
                                variant="ghost"
                                :label="$t('admin.search_settings')"
                                :ui="{ label: 'hidden sm:inline' }"
                                @click="searchOpen = true"
                            >
                                <template #trailing>
                                    <span class="hidden sm:flex items-center gap-0.5">
                                        <u-kbd value="meta" size="sm" />
                                        <u-kbd value="K" size="sm" />
                                    </span>
                                </template>
                            </u-button>
                            <slot name="actions" />
                        </template>
                    </u-dashboard-navbar>

                    <u-dashboard-toolbar v-if="$slots.toolbar">
                        <slot name="toolbar" />
                    </u-dashboard-toolbar>
                </template>

                <template #body>
                    <div class="space-y-6 pb-10">
                        <p v-if="description" class="text-sm text-muted -mt-2">{{ description }}</p>
                        <slot />
                    </div>
                </template>
            </u-dashboard-panel>
        </u-dashboard-group>

        <u-modal v-model:open="searchOpen" :title="$t('admin.search_settings')" :ui="{ content: 'sm:max-w-lg' }">
            <template #content>
                <u-command-palette
                    :groups="searchGroups"
                    :placeholder="$t('admin.search_settings_placeholder')"
                    :fuse="{ fuseOptions: { keys: ['label', 'suffix', 'key'] } }"
                    close
                    class="h-80"
                    @update:model-value="openSetting"
                    @update:open="searchOpen = $event"
                />
            </template>
        </u-modal>

        <template #fallback>
            <div class="flex min-h-screen">
                <div class="hidden lg:block w-64 shrink-0 border-r border-default bg-elevated/30" />
                <div class="flex-1 min-w-0 p-6 space-y-6">
                    <h1 class="text-xl font-semibold text-highlighted">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted">{{ description }}</p>
                    <slot />
                </div>
            </div>
        </template>
    </client-only>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AppLogo from '@/Components/AppLogo.vue';
import { useAuth } from '@/Composables/useAuth';

const props = defineProps({
    current: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: null },
});

const { isAdmin, canCreateTiles, canCreateBoards } = useAuth();

const sidebarOpen = ref(false);

const searchOpen = ref(false);
const inertiaPage = usePage();

// Built from the shared index (Setting::searchIndex), never a list kept
// here — a new setting shows up on its own.
const searchGroups = computed(() => [{
    id: 'settings',
    label: trans('admin.search_settings_group'),
    items: (inertiaPage.props.settingsIndex ?? []).map((setting) => ({
        key: setting.key,
        label: setting.label,
        suffix: setting.description ?? undefined,
        icon: 'i-lucide-sliders-horizontal',
        value: setting.key,
    })),
}]);

function openSetting(item) {
    if (!item?.value) return;
    searchOpen.value = false;
    router.visit(`/admin/site?setting=${item.value}`);
}

function onKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && isAdmin.value) {
        event.preventDefault();
        searchOpen.value = !searchOpen.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

/**
 * Grouped so the content and system halves read as separate concerns rather
 * than one long list.
 *
 * Filtered **per item**, not by one isAdmin check per group: Tasks is gated
 * on canCreateTiles, so an EDITOR sees Tasks and nothing else — verified
 * against the server, which returns 200 for tasks and 403 for the rest on
 * that account. Every page re-checks server-side regardless; this only
 * avoids advertising links that would 403.
 */
const navItems = computed(() => {
    const groups = [
        [
            {
                label: trans('admin.nav_dashboard'),
                icon: 'i-lucide-layout-dashboard',
                to: '/admin',
                active: props.current === 'dashboard',
                show: true,
            },
        ],
        [
            { label: trans('settings.nav_admin_users'), icon: 'i-lucide-user-cog', to: '/admin/users', active: props.current === 'users', show: isAdmin.value },
            { label: trans('settings.nav_admin_boards'), icon: 'i-lucide-layout-grid', to: '/admin/events', active: props.current === 'boards', show: isAdmin.value },
            { label: trans('settings.nav_admin_tasks'), icon: 'i-lucide-list-checks', to: '/admin/tasks', active: props.current === 'tasks', show: isAdmin.value || canCreateTiles.value },
            { label: trans('settings.nav_admin_blueprints'), icon: 'i-lucide-shapes', to: '/admin/blueprints', active: props.current === 'blueprints', show: isAdmin.value || canCreateBoards.value },
            { label: trans('settings.nav_admin_invites'), icon: 'i-lucide-ticket', to: '/admin/invites', active: props.current === 'invites', show: isAdmin.value },
            // Admin-only: a boss icon is site-wide presentation, not
            // content an editor makes for their own event.
            { label: trans('settings.nav_admin_boss_icons'), icon: 'i-lucide-image', to: '/admin/boss-icons', active: props.current === 'boss-icons', show: isAdmin.value },
        ],
        [
            { label: trans('settings.nav_admin_content'), icon: 'i-lucide-layout-template', to: '/admin/content', active: props.current === 'content', show: isAdmin.value },
            { label: trans('settings.nav_admin_site'), icon: 'i-lucide-sliders-horizontal', to: '/admin/site', active: props.current === 'site', show: isAdmin.value },
            { label: trans('settings.nav_admin_audit'), icon: 'i-lucide-scroll-text', to: '/admin/audit', active: props.current === 'audit', show: isAdmin.value },
            { label: trans('settings.nav_admin_diagnostics'), icon: 'i-lucide-stethoscope', to: '/admin/diagnostics', active: props.current === 'diagnostics', show: isAdmin.value },
        ],
    ];

    return groups
        .map((group) => group.filter((item) => item.show))
        .filter((group) => group.length);
});
</script>
