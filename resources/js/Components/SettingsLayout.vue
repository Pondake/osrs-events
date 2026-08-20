<template>
    <u-main>
        <u-page>
            <u-container class="max-w-5xl py-12">
                <h1 class="text-3xl font-bold text-highlighted mb-8">{{ $t('settings.title') }}</h1>

                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Plain <a> links, not u-navigation-menu: this nav lives inside
                         an Inertia page (unlike AppHeader's), so it has no SSR
                         ordering problem to work around — see AppHeader.vue's own
                         comment for why that one needs ClientOnly and this doesn't. -->
                    <nav class="w-full md:w-52 shrink-0 space-y-6">
                        <div v-for="group in groups" :key="group.key">
                            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wide text-muted">{{ group.label }}</p>
                            <div class="flex md:flex-col gap-1">
                                <a
                                    v-for="item in group.items"
                                    :key="item.to"
                                    :href="item.to"
                                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors"
                                    :class="current === item.key ? 'bg-elevated text-highlighted font-medium' : 'text-muted hover:bg-elevated/50'"
                                >
                                    <u-icon :name="item.icon" class="size-4 shrink-0" />
                                    {{ item.label }}
                                </a>
                            </div>
                        </div>
                    </nav>

                    <div class="flex-1 min-w-0 w-full space-y-8">
                        <slot />
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';

defineProps({
    current: { type: String, required: true },
});

const { isAdmin, canCreateTiles } = useAuth();

/**
 * Grouped so the admin half reads as a separate concern rather than more
 * personal settings.
 *
 * Admin items are filtered **per item**, not by one isAdmin check on the
 * whole group: Tasks is gated on canCreateTiles (see Admin\TaskController),
 * not on being an admin, so an EDITOR must still see it while seeing none
 * of the rest. Every page behind these re-checks server-side regardless —
 * this only avoids advertising links that would 403.
 */
const groups = computed(() => {
    const result = [
        {
            key: 'account',
            label: trans('settings.group_account'),
            items: [
                { key: 'profile', to: '/settings/profile', icon: 'i-lucide-user-circle', label: trans('settings.nav_profile') },
                { key: 'account', to: '/settings/account', icon: 'i-lucide-shield', label: trans('settings.nav_account') },
            ],
        },
    ];

    const adminItems = [
        { key: 'admin-users', to: '/settings/admin/users', icon: 'i-lucide-user-cog', label: trans('settings.nav_admin_users'), show: isAdmin.value },
        { key: 'admin-boards', to: '/settings/admin/boards', icon: 'i-lucide-layout-grid', label: trans('settings.nav_admin_boards'), show: isAdmin.value },
        { key: 'admin-tasks', to: '/settings/admin/tasks', icon: 'i-lucide-list-checks', label: trans('settings.nav_admin_tasks'), show: isAdmin.value || canCreateTiles.value },
        { key: 'admin-content', to: '/settings/admin/content', icon: 'i-lucide-layout-template', label: trans('settings.nav_admin_content'), show: isAdmin.value },
    ].filter((item) => item.show);

    if (adminItems.length) {
        result.push({ key: 'admin', label: trans('settings.group_admin'), items: adminItems });
    }

    return result;
});
</script>
