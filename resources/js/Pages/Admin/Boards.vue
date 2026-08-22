<template>
    <Head :title="$t('admin.boards_title')" />

    <admin-layout current="boards" :title="$t('admin.boards_title')" :description="$t('admin.boards_subtitle')">

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="board in boards" :key="board.id" class="flex items-center justify-between gap-4 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium truncate">{{ board.title }}</span>
                        <u-badge v-if="!board.is_listed" :label="$t('boards.unlisted')" size="xs" color="neutral" variant="subtle" />
                        <u-badge :label="board.access_mode" size="xs" color="primary" variant="subtle" />
                    </div>
                    <div class="text-xs text-muted truncate">
                        {{ board.authors.map((a) => a.user.nickname ?? a.user.discord_username).join(', ') || $t('admin.no_authors') }}
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <u-button :href="`/events/${board.id}`" icon="i-lucide-eye" size="xs" color="neutral" variant="ghost" :aria-label="$t('board.view_mode')" />
                    <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" :aria-label="$t('common.edit')" @click="editingBoard = board" />
                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="destroyBoard(board)" />
                </div>
            </div>
            <p v-if="!boards.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_boards') }}</p>
        </div>

        <client-only>
            <board-settings-modal
                :open="editingBoard !== null"
                :board="editingBoard"
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
    router.delete(`/admin/events/${board.id}`, { preserveScroll: true });
}
</script>
