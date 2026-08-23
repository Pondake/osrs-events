<template>
    <Head :title="$t('admin.boards_title')" />

    <admin-layout current="boards" :title="$t('admin.boards_title')" :description="$t('admin.boards_subtitle')">

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div
                v-for="board in boards"
                :key="board.id"
                class="flex items-center justify-between gap-4 px-4 py-3"
                :class="board.deleted_at ? 'opacity-60' : ''"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium truncate" :class="board.deleted_at ? 'line-through' : ''">{{ board.title }}</span>
                        <!-- This list is the only place a deleted event is
                             visible at all — everywhere else the soft delete
                             takes it out of the query. Which makes this the
                             only place it can be put back. -->
                        <u-badge v-if="board.deleted_at" :label="$t('admin.event_deleted_badge')" size="xs" color="error" variant="subtle" />
                        <u-badge v-if="board.paused_at" :label="$t('boards.status_paused')" size="xs" color="warning" variant="subtle" />
                        <u-badge v-if="!board.is_listed" :label="$t('boards.unlisted')" size="xs" color="neutral" variant="subtle" />
                        <u-badge :label="board.access_mode" size="xs" color="primary" variant="subtle" />
                    </div>
                    <div class="text-xs text-muted truncate">
                        {{ board.authors.map((a) => a.user.nickname ?? a.user.discord_username).join(', ') || $t('admin.no_authors') }}
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <u-button
                        v-if="board.deleted_at"
                        icon="i-lucide-undo-2"
                        size="xs"
                        color="primary"
                        variant="ghost"
                        :label="$t('admin.event_restore')"
                        @click="restoreBoard(board)"
                    />
                    <template v-else>
                        <u-button :href="`/events/${board.id}`" icon="i-lucide-eye" size="xs" color="neutral" variant="ghost" :aria-label="$t('board.view_mode')" />
                        <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" :aria-label="$t('common.edit')" @click="editingBoard = board" />
                        <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="destroyBoard(board)" />
                    </template>
                </div>
            </div>
            <p v-if="!boards.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_boards') }}</p>
        </div>

        <client-only>
            <board-settings-modal
                :open="editingBoard !== null"
                :board="editingBoard"
                :webhook-url="editingBoard?.discord_webhook_url ?? null"
                base-path="/admin/events"
                @update:open="(v) => !v && (editingBoard = null)"
            />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

defineProps({
    boards: { type: Array, required: true },
});

const editingBoard = ref(null);

function destroyBoard(board) {
    // The admin route, not the public one — an admin deleting somebody
    // else's event is exactly the power that no longer exists out there.
    //
    // notify: false. This list has no room to ask, and a delete from the
    // admin screen is usually housekeeping — a test event, a duplicate,
    // something abandoned. Announcing those to whoever once joined them
    // would be worse than saying nothing. The host's own danger zone is
    // where the question gets asked, because that is where the answer is
    // usually yes.
    router.delete(`/admin/events/${board.id}`, { data: { notify: false }, preserveScroll: true });
}

function restoreBoard(board) {
    router.post(`/admin/events/${board.id}/restore`, {}, { preserveScroll: true });
}
</script>
