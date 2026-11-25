<template>
    <Head :title="$t('admin.nav_dashboard')" />

    <admin-layout current="dashboard" :title="$t('admin.dashboard_title')" :description="$t('admin.dashboard_subtitle')">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <u-card v-for="list in userLists" :key="list.key">
                <template #header>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-semibold truncate">{{ list.title }}</span>
                            <u-badge
                                v-if="list.key === 'active'"
                                :label="$t('admin.dashboard_online_count', { count: onlineCount })"
                                :color="onlineCount ? 'success' : 'neutral'"
                                variant="subtle"
                                size="sm"
                                class="tabular-nums shrink-0"
                            />
                        </div>
                        <u-button href="/admin/users" size="xs" variant="ghost" color="neutral" trailing-icon="i-lucide-arrow-right" :label="$t('admin.dashboard_view_all')" />
                    </div>
                </template>

                <div v-if="list.users.length" class="divide-y divide-default -my-2">
                    <u-link
                        v-for="user in list.users"
                        :key="user.id"
                        :to="`/admin/users/${user.id}`"
                        raw
                        class="flex items-center gap-3 py-2.5 min-h-11 -mx-2 px-2 rounded-md hover:bg-elevated/60"
                    >
                        <u-avatar
                            :src="user.avatarUrl ?? undefined"
                            :alt="user.name"
                            size="sm"
                            :chip="user.online ? { color: 'success', position: 'bottom-right' } : undefined"
                        />
                        <span class="text-sm text-default truncate flex-1 min-w-0">{{ user.name }}</span>
                        <div class="text-xs tabular-nums shrink-0 text-end">
                            <div :class="user.online ? 'text-success font-medium' : 'text-muted'">{{ seenLabel(user) }}</div>
                            <div v-if="list.key === 'newest'" class="text-dimmed">{{ $t('admin.user_joined', { date: formatDate(user.createdAt) }) }}</div>
                        </div>
                    </u-link>
                </div>
                <p v-else class="text-sm text-muted py-2">{{ $t('admin.dashboard_no_activity_yet') }}</p>
            </u-card>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <u-page-card
                v-for="tile in tiles"
                :key="tile.key"
                :to="tile.to"
                variant="subtle"
                class="min-w-0"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <u-icon :name="tile.icon" class="size-4 shrink-0" :class="tile.iconClass" />
                    <span class="text-xs text-muted truncate">{{ tile.label }}</span>
                </div>
                <div class="text-2xl font-semibold text-highlighted tabular-nums mt-1">{{ tile.value }}</div>
            </u-page-card>
        </div>

        <u-card>
            <template #header>
                <div class="flex items-center justify-between gap-3">
                    <span class="font-semibold">{{ $t('admin.dashboard_activity') }}</span>
                    <u-button href="/admin/audit" size="xs" variant="ghost" color="neutral" trailing-icon="i-lucide-arrow-right" :label="$t('admin.dashboard_view_all')" />
                </div>
            </template>

            <div v-if="recentActivity.length" class="divide-y divide-default -my-2">
                <div v-for="entry in recentActivity" :key="entry.id" class="flex items-start gap-3 py-2.5">
                    <u-icon :name="auditStyleFor(entry.action).icon" class="size-4 shrink-0 mt-0.5" :class="actionClass(entry.action)" />
                    <div class="min-w-0 flex-1">
                        <!-- Flex gap, not a space inside the span: Vue's
                             whitespace condensing drops a leading space in a
                             text node, so the two ran together. -->
                        <div class="flex items-baseline gap-1.5 flex-wrap text-sm">
                            <span class="font-medium">{{ auditLabel(entry.action) }}</span>
                            <span class="text-muted">{{ $t('admin.audit_by', { actor: entry.actor_label }) }}</span>
                        </div>
                        <p v-if="entry.target_label" class="text-xs text-muted truncate">{{ entry.target_label }}</p>
                    </div>
                    <span class="text-xs text-dimmed tabular-nums shrink-0">{{ formatTimestamp(entry.created_at) }}</span>
                </div>
            </div>
            <p v-else class="text-sm text-muted py-2">{{ $t('admin.audit_empty') }}</p>
        </u-card>
    </admin-layout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AdminLayout from '@/Components/AdminLayout.vue';
import { auditLabel, auditStyleFor, formatTimestamp } from '@/Support/audit';
import { formatDate } from '@/Support/invite';
import { relativeTime } from '@/Support/board';

const props = defineProps({
    stats: { type: Object, required: true },
    recentActivity: { type: Array, required: true },
    onlineCount: { type: Number, required: true },
    recentlyActiveUsers: { type: Array, required: true },
    newestUsers: { type: Array, required: true },
});

const userLists = computed(() => [
    { key: 'active', title: trans('admin.dashboard_recently_active'), users: props.recentlyActiveUsers },
    { key: 'newest', title: trans('admin.dashboard_newest_users'), users: props.newestUsers },
]);

function seenLabel(user) {
    if (user.online) return trans('admin.user_online');
    if (!user.lastSeenAt) return trans('admin.user_never_seen');
    return trans('admin.user_last_seen', { time: relativeTime(user.lastSeenAt) });
}

// Written out per colour rather than interpolated — Tailwind scans source
// text, so a `text-${color}` class is never generated.
const ACTION_CLASS = {
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
    neutral: 'text-muted',
};

function actionClass(action) {
    return ACTION_CLASS[auditStyleFor(action).color] ?? ACTION_CLASS.neutral;
}

// Each tile links to the page that manages the thing it counts — a number
// with nowhere to go is just decoration.
const tiles = computed(() => [
    { key: 'users', label: trans('settings.nav_admin_users'), value: props.stats.users, icon: 'i-lucide-users', iconClass: 'text-primary', to: '/admin/users' },
    { key: 'boards', label: trans('settings.nav_admin_boards'), value: props.stats.boards, icon: 'i-lucide-layout-grid', iconClass: 'text-primary', to: '/admin/events' },
    { key: 'teams', label: trans('admin.dashboard_teams'), value: props.stats.teams, icon: 'i-lucide-users-round', iconClass: 'text-primary', to: '/teams' },
    { key: 'tasks', label: trans('settings.nav_admin_tasks'), value: props.stats.tasks, icon: 'i-lucide-list-checks', iconClass: 'text-primary', to: '/admin/tasks' },
    { key: 'invites', label: trans('admin.dashboard_live_invites'), value: props.stats.liveInvites, icon: 'i-lucide-ticket', iconClass: 'text-success', to: '/admin/invites' },
]);
</script>
