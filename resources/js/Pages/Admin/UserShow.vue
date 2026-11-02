<template>
    <Head :title="profile.name" />

    <admin-layout current="users" :title="profile.name">
        <template #actions>
            <u-button to="/admin/users" size="sm" variant="ghost" color="neutral" icon="i-lucide-arrow-left" :label="$t('admin.user_back')" />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="space-y-6 min-w-0">
                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_account') }}</span>
                    </template>

                    <div class="flex items-center gap-3">
                        <u-avatar :src="profile.avatarUrl ?? undefined" :alt="profile.name" size="lg" />
                        <div class="min-w-0 space-y-0.5">
                            <div v-if="profile.discordUsername" class="text-sm truncate">@{{ profile.discordUsername }}</div>
                            <div v-if="profile.email" class="text-sm text-muted truncate">{{ profile.email }}</div>
                            <u-badge v-if="profile.isSelf" :label="$t('admin.user_you')" size="xs" color="neutral" variant="subtle" />
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm [&_dd]:whitespace-nowrap">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_joined_on') }}</dt>
                            <dd class="tabular-nums">{{ formatDate(profile.createdAt) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_onboarded') }}</dt>
                            <dd class="tabular-nums">{{ formatDate(profile.onboardedAt) ?? $t('admin.user_not_yet') }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <u-badge v-for="role in profile.roles" :key="role" :label="role" :color="roleColor(role)" variant="subtle" />
                        <span v-if="!profile.roles.length" class="text-sm text-muted">{{ $t('admin.user_no_roles') }}</span>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div v-for="entry in profile.access" :key="entry.key" class="flex justify-between gap-3">
                            <dt class="text-muted">{{ permissionLabel(entry.key) }}</dt>
                            <dd class="text-right" :class="entry.source ? 'text-success' : 'text-dimmed'">{{ accessLabel(entry) }}</dd>
                        </div>
                    </dl>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_characters') }}</span>
                    </template>

                    <ul v-if="characters.length" class="divide-y divide-default -my-2">
                        <li v-for="character in characters" :key="character.username" class="flex items-center gap-2 py-2.5 text-sm">
                            <span class="truncate flex-1 min-w-0">{{ character.username }}</span>
                            <u-badge v-if="character.isMain" :label="$t('admin.user_main')" size="xs" color="primary" variant="subtle" />
                            <u-icon v-if="character.provenAt" name="i-lucide-shield-check" class="size-4 text-success shrink-0" :title="$t('admin.user_proven')" />
                            <u-icon v-else-if="character.verifiedAt" name="i-lucide-check" class="size-4 text-muted shrink-0" :title="$t('admin.user_verified')" />
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted">{{ $t('admin.user_no_characters') }}</p>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_connections') }}</span>
                    </template>

                    <dl class="space-y-2 text-sm [&_dd]:whitespace-nowrap">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_discord') }}</dt>
                            <dd :class="connections.discord ? 'text-success' : 'text-dimmed'">{{ connections.discord ? $t('admin.user_connected') : $t('admin.user_not_connected') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_password') }}</dt>
                            <dd :class="connections.password ? 'text-success' : 'text-dimmed'">{{ connections.password ? $t('admin.user_connected') : $t('admin.user_not_connected') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_push_devices') }}</dt>
                            <dd class="tabular-nums">{{ connections.pushDevices }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ $t('admin.user_plugin') }}</dt>
                            <dd class="text-right">
                                <template v-if="!connections.plugin"><span class="text-dimmed">{{ $t('admin.user_not_connected') }}</span></template>
                                <template v-else-if="connections.plugin.last_used_at">{{ $t('admin.user_plugin_last_used', { date: formatDate(connections.plugin.last_used_at) }) }}</template>
                                <template v-else>{{ $t('admin.user_plugin_never_used') }}</template>
                            </dd>
                        </div>
                    </dl>

                    <details v-if="connections.guilds.length" class="group mt-3">
                        <summary class="flex items-center justify-between gap-3 min-h-11 text-sm list-none rounded-md hover:text-highlighted">
                            <span class="text-muted">{{ $t('admin.user_guilds') }}</span>
                            <span class="flex items-center gap-1 tabular-nums">
                                {{ connections.guilds.length }}
                                <u-icon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform group-open:rotate-180" />
                            </span>
                        </summary>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <u-badge v-for="guild in connections.guilds" :key="guild" :label="guild" color="neutral" variant="subtle" />
                        </div>
                    </details>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_teams') }}</span>
                    </template>

                    <ul v-if="teams.length" class="divide-y divide-default -my-2">
                        <li v-for="team in teams" :key="team.id" class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span class="truncate min-w-0">{{ team.name }}</span>
                            <span class="text-xs text-muted shrink-0">{{ teamRoleLabel(team.role) }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted">{{ $t('admin.user_no_teams') }}</p>
                </u-card>
            </div>

            <div class="lg:col-span-2 space-y-6 min-w-0">
                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_events') }}</span>
                    </template>

                    <ul v-if="events.length" class="divide-y divide-default -my-2">
                        <li v-for="event in events" :key="event.id">
                            <component
                                :is="event.status === 'deleted' ? 'div' : Link"
                                v-bind="event.status === 'deleted' ? {} : { href: `/events/${event.id}` }"
                                class="flex items-center gap-3 py-2.5 min-h-11 -mx-2 px-2 rounded-md"
                                :class="event.status === 'deleted' ? 'opacity-60' : 'hover:bg-elevated/60'"
                            >
                                <u-icon :name="eventTypeMeta(event.type)?.icon ?? 'i-lucide-calendar'" class="size-4 shrink-0 text-muted" />
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-highlighted truncate">{{ event.title }}</div>
                                    <div class="text-xs text-muted truncate">{{ event.links.map(linkLabel).join(' · ') }}</div>
                                </div>
                                <u-badge :label="statusLabel(event.status)" :color="STATUS_COLORS[event.status]" size="xs" variant="subtle" class="shrink-0" />
                                <span class="hidden sm:inline text-xs text-dimmed tabular-nums shrink-0">{{ formatDate(event.startDate) }}</span>
                            </component>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted">{{ $t('admin.user_no_events') }}</p>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('admin.user_claims') }}</span>
                    </template>

                    <ul v-if="claims.length" class="divide-y divide-default -my-2">
                        <li v-for="claim in claims" :key="claim.id">
                            <component
                                :is="claimLinks(claim) ? Link : 'div'"
                                v-bind="claimLinks(claim) ? { href: `/events/${claim.event.id}` } : {}"
                                class="flex items-center gap-3 py-2.5 min-h-11 -mx-2 px-2 rounded-md"
                                :class="claimLinks(claim) ? 'hover:bg-elevated/60' : ''"
                            >
                                <u-icon :name="claim.via === 'RUNELITE' ? 'i-lucide-plug' : 'i-lucide-hand'" class="size-4 shrink-0 text-muted" />
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm truncate">{{ claim.label ?? '—' }}</div>
                                    <div v-if="claim.event" class="text-xs text-muted truncate">{{ claim.event.title }}</div>
                                </div>
                                <u-badge :label="claimStatusLabel(claim.status)" :color="CLAIM_COLORS[claim.status] ?? 'neutral'" size="xs" variant="subtle" class="shrink-0" />
                                <span class="hidden sm:inline text-xs text-dimmed tabular-nums shrink-0">{{ formatTimestamp(claim.at) }}</span>
                            </component>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted">{{ $t('admin.user_no_claims') }}</p>
                </u-card>

                <u-card>
                    <template #header>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold">{{ $t('admin.user_activity') }}</span>
                            <u-button v-if="activity.length" :to="`/admin/audit?user=${encodeURIComponent(profile.name)}`" size="xs" variant="ghost" color="neutral" trailing-icon="i-lucide-arrow-right" :label="$t('admin.dashboard_view_all')" />
                        </div>
                    </template>

                    <div v-if="activity.length" class="divide-y divide-default -my-2">
                        <div v-for="entry in activity" :key="entry.id" class="flex items-start gap-3 py-2.5">
                            <u-icon :name="auditStyleFor(entry.action).icon" class="size-4 shrink-0 mt-0.5 text-muted" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline gap-1.5 flex-wrap text-sm">
                                    <span class="font-medium">{{ auditLabel(entry.action) }}</span>
                                    <span class="text-muted">{{ $t('admin.audit_by', { actor: entry.actor_label }) }}</span>
                                </div>
                                <p v-if="entry.target_label" class="text-xs text-muted truncate">{{ entry.target_label }}</p>
                            </div>
                            <span class="text-xs text-dimmed tabular-nums shrink-0">{{ formatTimestamp(entry.created_at) }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted">{{ $t('admin.user_no_activity', { days: auditRetentionDays }) }}</p>
                </u-card>
            </div>
        </div>
    </admin-layout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AdminLayout from '@/Components/AdminLayout.vue';
import { auditLabel, auditStyleFor, formatTimestamp, permissionLabel } from '@/Support/audit';
import { eventTypeMeta } from '@/Support/eventTypes';
import { formatDate } from '@/Support/invite';

defineProps({
    profile: { type: Object, required: true },
    characters: { type: Array, required: true },
    connections: { type: Object, required: true },
    events: { type: Array, required: true },
    teams: { type: Array, required: true },
    claims: { type: Array, required: true },
    activity: { type: Array, required: true },
    auditRetentionDays: { type: Number, required: true },
});

function accessLabel(entry) {
    if (entry.source === 'direct') return trans('admin.user_access_direct');
    if (entry.source === 'role') return trans('admin.user_access_role', { role: entry.role });
    if (entry.source === 'admin') return trans('admin.user_access_admin');

    return trans('admin.user_access_none');
}

const claimLinks = (claim) => claim.event && !claim.event.deleted;

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', PLAYER: 'primary' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';

const STATUS_COLORS = { live: 'success', upcoming: 'info', paused: 'warning', ended: 'neutral', deleted: 'error' };
const STATUS_LABELS = {
    live: 'boards.status_live',
    upcoming: 'boards.status_upcoming',
    paused: 'boards.status_paused',
    ended: 'boards.status_ended',
    deleted: 'admin.events_status_deleted',
};
const statusLabel = (status) => trans(STATUS_LABELS[status]);

const CLAIM_COLORS = { APPROVED: 'success', PENDING: 'warning', REJECTED: 'error' };
const claimStatusLabel = (status) => trans(`bingo.status_${status.toLowerCase()}`);

const teamRoleLabel = (role) => trans(`teams.role_${role.toLowerCase()}`);

function linkLabel(link) {
    return link.kind === 'team'
        ? trans('admin.user_link_team', { team: link.team ?? '' })
        : trans(`admin.user_link_${link.kind}`);
}
</script>
