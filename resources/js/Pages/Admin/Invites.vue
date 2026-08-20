<template>
    <Head :title="$t('settings.nav_admin_invites')" />

    <admin-layout current="invites" :title="$t('settings.nav_admin_invites')" :description="$t('admin.invites_subtitle')">

        <!-- Counts are for the whole table, not the filtered page, so they
             stay a fixed reference while you narrow. Clicking one filters. -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button
                v-for="tile in summaryTiles"
                :key="tile.key"
                type="button"
                class="rounded-lg ring bg-default px-3 py-2 text-left transition-colors"
                :class="status === tile.key ? 'ring-primary' : 'ring-default hover:bg-elevated/50'"
                @click="status = status === tile.key ? null : tile.key"
            >
                <div class="flex items-center gap-1.5">
                    <u-icon :name="tile.icon" class="size-3.5 shrink-0" :class="tile.iconClass" />
                    <span class="text-xs text-muted truncate">{{ tile.label }}</span>
                </div>
                <div class="text-xl font-semibold text-highlighted tabular-nums">{{ tile.count }}</div>
            </button>
        </div>

        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <u-input
                    v-model="search"
                    icon="i-lucide-search"
                    :placeholder="$t('admin.invites_search_placeholder')"
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <u-select
                    v-model="status"
                    :items="statusOptions"
                    :placeholder="$t('admin.invite_all_statuses')"
                    class="w-full"
                    :ui="{ value: 'pe-6' }"
                >
                    <template #item-leading="{ item }">
                        <u-icon :name="item.icon" class="size-4" />
                    </template>
                    <template #trailing>
                        <filter-clear :show="!!status" @clear="status = null" />
                    </template>
                </u-select>

                <u-input-menu
                    v-model="board"
                    :items="boards"
                    value-key="value"
                    label-key="label"
                    :placeholder="$t('admin.invite_filter_board')"
                    icon="i-lucide-layout-grid"
                    class="w-full"
                    :ui="{ base: 'pe-14' }"
                >
                    <template #trailing>
                        <filter-clear :show="!!board" @clear="board = null" />
                    </template>
                </u-input-menu>

                <u-input-menu
                    v-model="creator"
                    :items="creators"
                    value-key="value"
                    label-key="label"
                    :placeholder="$t('admin.invite_filter_creator')"
                    icon="i-lucide-user"
                    class="w-full"
                    :ui="{ base: 'pe-14' }"
                >
                    <template #trailing>
                        <filter-clear :show="!!creator" @clear="creator = null" />
                    </template>
                </u-input-menu>
            </div>
        </div>

        <div v-if="invites.data.length" class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="invite in invites.data" :key="invite.id" class="flex items-start gap-3 px-4 py-3">
                <u-icon :name="styleFor(invite.status).icon" class="size-4 shrink-0 mt-1" :class="statusClass(invite.status)" />

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="font-medium">{{ invite.label || $t('admin.invite_untitled') }}</span>
                        <!-- The code someone actually pastes into chat, so it
                             gets a monospace face to be read back accurately. -->
                        <code class="text-xs font-mono px-1.5 py-0.5 rounded bg-elevated text-highlighted">{{ invite.short_code }}</code>
                        <u-badge
                            :label="statusLabel(invite.status)"
                            :color="styleFor(invite.status).color"
                            variant="subtle"
                            size="sm"
                        />
                    </div>

                    <div class="flex items-center gap-2 flex-wrap mt-0.5 text-xs text-muted">
                        <a v-if="invite.board" :href="`/events/${invite.board.id}`" class="inline-flex items-center gap-1 hover:text-highlighted transition-colors">
                            <u-icon name="i-lucide-layout-grid" class="size-3 shrink-0" />
                            {{ invite.board.title }}
                        </a>
                        <span v-if="invite.creator" class="inline-flex items-center gap-1">
                            <u-icon name="i-lucide-user" class="size-3 shrink-0" />
                            {{ $t('admin.invite_created_by', { name: invite.creator }) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-x-4 gap-y-1 flex-wrap mt-1 text-xs">
                        <span class="text-muted">
                            {{ $t('admin.invite_uses') }}
                            <span class="text-highlighted">{{ usage(invite) }}</span>
                        </span>
                        <span class="text-muted">
                            {{ $t('admin.invite_joined') }}
                            <span class="text-highlighted">{{ invite.accepted }}</span>
                        </span>
                        <span class="text-muted">
                            {{ $t('admin.invite_expires_label') }}
                            <span class="text-highlighted">{{ date(invite.expires_at) ?? $t('admin.invite_never') }}</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-xs text-muted tabular-nums text-right">{{ date(invite.created_at) }}</div>
                    <u-button
                        icon="i-lucide-trash-2"
                        color="error"
                        variant="ghost"
                        size="xs"
                        :aria-label="$t('admin.invite_revoke')"
                        @click="confirmRevoke(invite)"
                    />
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12 rounded-lg ring ring-default bg-default">
            <u-icon name="i-lucide-ticket" class="size-8 text-dimmed mx-auto mb-2" />
            <p class="text-sm text-muted">{{ hasFilters ? $t('admin.invites_empty_filtered') : $t('admin.invites_empty') }}</p>
        </div>

        <div v-if="invites.last_page > 1" class="flex items-center justify-between gap-3">
            <p class="text-sm text-muted">{{ $t('admin.audit_page_of', { current: invites.current_page, last: invites.last_page, total: invites.total }) }}</p>
            <div class="flex gap-2">
                <u-button
                    :href="invites.prev_page_url ?? undefined"
                    :disabled="!invites.prev_page_url"
                    color="neutral"
                    variant="subtle"
                    icon="i-lucide-chevron-left"
                    :label="$t('common.previous')"
                />
                <u-button
                    :href="invites.next_page_url ?? undefined"
                    :disabled="!invites.next_page_url"
                    color="neutral"
                    variant="subtle"
                    trailing-icon="i-lucide-chevron-right"
                    :label="$t('common.next')"
                />
            </div>
        </div>

        <client-only>
            <u-modal v-model:open="revoking" :title="$t('admin.invite_revoke')">
                <template #body>
                    <!-- Says what revoking does NOT do: the natural fear is
                         that it kicks everyone who already joined. -->
                    <p class="text-sm">{{ $t('admin.invite_revoke_confirm', { label: revokeTarget?.label || revokeTarget?.short_code }) }}</p>
                </template>
                <template #footer>
                    <div class="flex justify-end gap-2 w-full">
                        <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="revoking = false" />
                        <u-button color="error" icon="i-lucide-trash-2" :label="$t('admin.invite_revoke')" @click="revoke" />
                    </div>
                </template>
            </u-modal>
        </client-only>
    </admin-layout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import FilterClear from '@/Components/FilterClear.vue';
import { formatDate, inviteStatusLabel, inviteStatusOptions, inviteStyleFor, usageLabel } from '@/Support/invite';

const props = defineProps({
    invites: { type: Object, required: true },
    statuses: { type: Array, required: true },
    boards: { type: Array, required: true },
    creators: { type: Array, required: true },
    totals: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status || null);
const board = ref(props.filters.board || null);
const creator = ref(props.filters.creator || null);

const statusOptions = computed(() => inviteStatusOptions(props.statuses));
const hasFilters = computed(() => Boolean(search.value || status.value || board.value || creator.value));

// Written out per colour rather than interpolated: Tailwind scans source
// text, so a `text-${color}` is never generated.
const STATUS_CLASS = {
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
    neutral: 'text-muted',
};

const styleFor = inviteStyleFor;
const statusLabel = inviteStatusLabel;
const usage = usageLabel;
const date = formatDate;

function statusClass(value) {
    return STATUS_CLASS[inviteStyleFor(value).color] ?? STATUS_CLASS.neutral;
}

const summaryTiles = computed(() =>
    props.statuses.map((key) => ({
        key,
        label: inviteStatusLabel(key),
        count: props.totals[key] ?? 0,
        icon: inviteStyleFor(key).icon,
        iconClass: statusClass(key),
    })),
);

let timer;
watch([search, status, board, creator], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/admin/invites',
            {
                search: search.value || undefined,
                status: status.value || undefined,
                board: board.value || undefined,
                creator: creator.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function clearFilters() {
    search.value = '';
    status.value = null;
    board.value = null;
    creator.value = null;
}

const revoking = ref(false);
const revokeTarget = ref(null);

function confirmRevoke(invite) {
    revokeTarget.value = invite;
    revoking.value = true;
}

function revoke() {
    const id = revokeTarget.value?.id;
    revoking.value = false;

    router.delete(`/admin/invites/${id}`, {
        preserveScroll: true,
        onError: (errors) => {
            console.error(errors);
        },
    });
}
</script>
