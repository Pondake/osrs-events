<template>
    <Head :title="$t('boards.mine_title')">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ $t('boards.mine_title') }}</h1>
                        <p class="text-sm text-muted mt-1">{{ $t('boards.mine_subtitle') }}</p>
                    </div>

                    <u-button
                        v-if="canCreateBoards"
                        color="primary"
                        icon="i-lucide-plus"
                        :label="$t('admin.create_board')"
                        @click="showCreateModal = true"
                    />
                </div>

                <div v-if="!boards.length" class="text-center py-16">
                    <u-icon name="i-lucide-layout-grid" class="size-12 text-muted mx-auto mb-4" />
                    <p class="text-lg font-medium">{{ $t('boards.mine_empty') }}</p>
                    <p class="text-sm text-muted mt-1 mb-6">{{ $t('boards.mine_empty_desc') }}</p>
                    <u-button href="/events" color="primary" icon="i-lucide-compass" :label="$t('boards.browse_all')" />
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <board-card v-for="entry in boards" :key="entry.board.id" :board="entry.board" :progress="entry.progress" />
                </div>
            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal v-model:open="showCreateModal" :board="null" />
        </client-only>
    </u-main>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import BoardCard from '@/Components/BoardCard.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

defineProps({
    // [{ board, progress: { current, total, pct } }]
    boards: { type: Array, required: true },
});

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);
</script>
