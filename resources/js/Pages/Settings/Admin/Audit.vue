<template>
    <Head :title="$t('settings.nav_admin_audit')" />

    <settings-layout current="admin-audit">
        <div>
            <h2 class="text-xl font-semibold text-highlighted">{{ $t('settings.nav_admin_audit') }}</h2>
            <p class="text-sm text-muted mt-0.5">{{ $t('admin.audit_subtitle') }}</p>
        </div>

        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <u-input
                    v-model="search"
                    icon="i-lucide-search"
                    :placeholder="$t('admin.audit_search_placeholder')"
                    class="flex-1"
                />
                <u-button
                    v-if="hasFilters"
                    color="neutral"
                    variant="ghost"
                    icon="i-lucide-x"
                    :label="$t('admin.audit_clear')"
                    class="shrink-0"
                    @click="clearFilters"
                />
            </div>

            <!-- Three narrowing dimensions, each independent: what happened,
                 who it involved, and which team or clan it belonged to. -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <u-select
                    v-model="action"
                    :items="actionOptions"
                    :placeholder="$t('admin.audit_all_actions')"
                    class="w-full"
                    :ui="{ value: 'pe-6' }"
                >
                    <template #item-leading="{ item }">
                        <u-icon :name="item.icon" class="size-4" />
                    </template>
                    <!-- Beside the chevron rather than replacing it (which is
                         what the built-in `clear` prop does), so the control
                         still reads as a dropdown while it holds a value.
                         A u-icon, not a u-button: this slot renders inside the
                         component's own trigger button, and a nested <button>
                         is invalid markup. @click.stop keeps clearing from
                         also toggling the menu open. -->
                    <template #trailing>
                        <u-icon
                            v-if="action"
                            name="i-lucide-x"
                            class="size-4 ms-1.5 shrink-0 text-dimmed hover:text-highlighted cursor-pointer transition-colors"
                            role="button"
                            :aria-label="$t('admin.audit_clear_filter')"
                            @click.stop="action = null"
                        />
                        <u-icon name="i-lucide-chevron-down" class="size-4 shrink-0 text-dimmed" />
                    </template>
                </u-select>

                <!-- Type-ahead rather than a plain select: this list is every
                     person the log has ever touched, including deleted ones,
                     so it grows with the log and scrolling it is no way to
                     find anyone. Filtering is client-side over the full list;
                     if that list ever gets big enough to matter, it should
                     become a search endpoint rather than a bigger payload. -->
                <u-input-menu
                    v-model="user"
                    :items="userOptions"
                    :placeholder="$t('admin.audit_filter_user')"
                    icon="i-lucide-user"
                    class="w-full"
                    :ui="{ base: 'pe-14' }"
                >
                    <template #trailing>
                        <u-icon
                            v-if="user"
                            name="i-lucide-x"
                            class="size-4 ms-1.5 shrink-0 text-dimmed hover:text-highlighted cursor-pointer transition-colors"
                            role="button"
                            :aria-label="$t('admin.audit_clear_filter')"
                            @click.stop="user = null"
                        />
                        <u-icon name="i-lucide-chevron-down" class="size-4 shrink-0 text-dimmed" />
                    </template>
                </u-input-menu>

                <u-input-menu
                    v-model="scope"
                    :items="scopeOptions"
                    value-key="value"
                    label-key="label"
                    :placeholder="$t('admin.audit_filter_scope')"
                    icon="i-lucide-users"
                    class="w-full"
                    :ui="{ base: 'pe-14' }"
                >
                    <!-- A team and the clan it belongs to can share a name, so
                         the icon is what tells the two apart. -->
                    <template #item-leading="{ item }">
                        <u-icon :name="item.icon" class="size-4" />
                    </template>
                    <template #trailing>
                        <u-icon
                            v-if="scope"
                            name="i-lucide-x"
                            class="size-4 ms-1.5 shrink-0 text-dimmed hover:text-highlighted cursor-pointer transition-colors"
                            role="button"
                            :aria-label="$t('admin.audit_clear_filter')"
                            @click.stop="scope = null"
                        />
                        <u-icon name="i-lucide-chevron-down" class="size-4 shrink-0 text-dimmed" />
                    </template>
                </u-input-menu>
            </div>
        </div>

        <div v-if="logs.data.length" class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="log in logs.data" :key="log.id" class="flex items-start gap-3 px-4 py-3">
                <u-icon :name="styleFor(log.action).icon" class="size-4 shrink-0 mt-1" :class="iconClass(log.action)" />

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="font-medium">{{ label(log.action) }}</span>
                        <!-- actor_label, not a relation: the actor may since
                             have been deleted, which is exactly when this row
                             matters most. -->
                        <span class="text-sm text-muted">{{ $t('admin.audit_by', { actor: log.actor_label }) }}</span>
                    </div>

                    <p v-if="log.target_label" class="text-sm text-highlighted truncate">{{ log.target_label }}</p>

                    <!-- Clickable: seeing "in Iron Fist" and wanting the rest
                         of that team's history is the obvious next step. -->
                    <div v-if="log.team_label || log.guild_label" class="flex items-center gap-2 flex-wrap mt-0.5">
                        <button
                            v-if="log.team_label"
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-muted hover:text-highlighted transition-colors"
                            @click="scope = `team:${log.team_id}`"
                        >
                            <u-icon name="i-lucide-users" class="size-3 shrink-0" />
                            {{ log.team_label }}
                        </button>
                        <button
                            v-if="log.guild_label"
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-muted hover:text-highlighted transition-colors"
                            @click="scope = `guild:${log.guild_id}`"
                        >
                            <u-icon name="i-lucide-shield" class="size-3 shrink-0" />
                            {{ log.guild_label }}
                        </button>
                    </div>

                    <audit-metadata :metadata="log.metadata" />
                </div>

                <div class="text-right shrink-0">
                    <div class="text-xs text-muted tabular-nums">{{ timestamp(log.created_at) }}</div>
                    <div v-if="log.ip_address" class="text-xs text-dimmed tabular-nums">{{ log.ip_address }}</div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12 rounded-lg ring ring-default bg-default">
            <u-icon name="i-lucide-scroll-text" class="size-8 text-dimmed mx-auto mb-2" />
            <p class="text-sm text-muted">{{ hasFilters ? $t('admin.audit_empty_filtered') : $t('admin.audit_empty') }}</p>
        </div>

        <div v-if="logs.last_page > 1" class="flex items-center justify-between gap-3">
            <p class="text-sm text-muted">{{ $t('admin.audit_page_of', { current: logs.current_page, last: logs.last_page, total: logs.total }) }}</p>
            <div class="flex gap-2">
                <!-- Paginator URLs already carry the active filters
                     (withQueryString), so these need no query handling. -->
                <u-button
                    :href="logs.prev_page_url ?? undefined"
                    :disabled="!logs.prev_page_url"
                    color="neutral"
                    variant="subtle"
                    icon="i-lucide-chevron-left"
                    :label="$t('common.previous')"
                />
                <u-button
                    :href="logs.next_page_url ?? undefined"
                    :disabled="!logs.next_page_url"
                    color="neutral"
                    variant="subtle"
                    trailing-icon="i-lucide-chevron-right"
                    :label="$t('common.next')"
                />
            </div>
        </div>
    </settings-layout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import AuditMetadata from '@/Components/AuditMetadata.vue';
import { auditActionOptions, auditLabel, auditStyleFor, formatTimestamp } from '@/Support/audit';

const props = defineProps({
    logs: { type: Object, required: true },
    actions: { type: Array, required: true },
    users: { type: Array, required: true },
    scopes: { type: Array, required: true },
    filters: { type: Object, required: true },
});

// null, not '', for the three menus: an empty string is a value as far as
// u-input-menu is concerned and shows as a selected-but-blank entry, where
// null leaves the placeholder in place.
const search = ref(props.filters.search ?? '');
const action = ref(props.filters.action || null);
const user = ref(props.filters.user || null);
const scope = ref(props.filters.scope || null);

const actionOptions = computed(() => auditActionOptions(props.actions));

// Plain strings: the value IS the label here, because the options come from
// the log's own stored labels rather than from a users table (see
// AuditLogController::userOptions — deleted users have to stay selectable).
const userOptions = computed(() => props.users);

const scopeOptions = computed(() =>
    props.scopes.map((entry) => ({
        ...entry,
        icon: entry.type === 'guild' ? 'i-lucide-shield' : 'i-lucide-users',
    })),
);

const hasFilters = computed(() => Boolean(search.value || action.value || user.value || scope.value));

// Written out per colour for the same reason AppRoot's banner is: Tailwind
// scans source text, so an interpolated text-${color} is never generated.
const ICON_CLASS = {
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
    neutral: 'text-muted',
};

const styleFor = auditStyleFor;
const label = auditLabel;
const timestamp = formatTimestamp;

function iconClass(actionKey) {
    return ICON_CLASS[auditStyleFor(actionKey).color] ?? ICON_CLASS.neutral;
}

// Debounced so typing doesn't fire a request per keystroke. All four filters
// share one timer rather than having their own — it's a single "filters
// changed" signal either way, and separate timers could race into two visits
// for one change.
let timer;
watch([search, action, user, scope], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/settings/admin/audit',
            {
                search: search.value || undefined,
                action: action.value || undefined,
                user: user.value || undefined,
                scope: scope.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function clearFilters() {
    search.value = '';
    action.value = null;
    user.value = null;
    scope.value = null;
}
</script>
