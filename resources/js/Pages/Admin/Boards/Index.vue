<template>
    <Head title="Manage boards" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <h1 class="text-3xl font-bold text-highlighted mb-8">All boards</h1>

                <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
                    <div v-for="board in boards" :key="board.id" class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium truncate">{{ board.title }}</span>
                                <u-badge v-if="!board.is_listed" label="Unlisted" size="xs" color="neutral" variant="subtle" />
                                <u-badge :label="board.access_mode" size="xs" color="primary" variant="subtle" />
                            </div>
                            <div class="text-xs text-muted truncate">
                                {{ board.authors.map((a) => a.user.nickname ?? a.user.discord_username).join(', ') || 'No authors' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <u-button :href="`/boards/${board.id}`" icon="i-lucide-eye" size="xs" color="neutral" variant="ghost" label="View" />
                            <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" @click="editingBoard = board" />
                            <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" @click="destroyBoard(board)" />
                        </div>
                    </div>
                    <p v-if="!boards.length" class="px-4 py-8 text-center text-muted text-sm">No boards yet.</p>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal :open="editingBoard !== null" :board="editingBoard" @update:open="(v) => !v && (editingBoard = null)" />
        </client-only>
    </u-main>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

defineProps({
    boards: { type: Array, required: true },
});

const editingBoard = ref(null);

function destroyBoard(board) {
    router.delete(`/boards/${board.id}`, { preserveScroll: true });
}
</script>
