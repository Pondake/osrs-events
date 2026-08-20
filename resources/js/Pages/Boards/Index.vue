<template>
    <!-- This is a public, indexable page (BoardController::index is
         deliberately outside the auth middleware) but it carried no meta at
         all — no canonical, no og:, no twitter card. The seo.boards_*
         translation keys already existed and were simply never wired up. -->
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <h1 class="text-3xl font-bold text-highlighted">{{ $t('boards.title') }}</h1>

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
                    <p class="text-lg font-medium">{{ $t('boards.no_boards') }}</p>
                    <p class="text-sm text-muted mt-1">{{ $t('boards.no_boards_desc') }}</p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <board-card v-for="board in boards" :key="board.id" :board="board" />
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
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import BoardCard from '@/Components/BoardCard.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import SeoHead from '@/Components/SeoHead.vue';

const seo = {
    title: trans('seo.boards_title'),
    description: trans('seo.boards_desc'),
};

// Dynamic import, not a static one — a static import would still pull
// BoardSettingsModal (and the @nuxt/ui form components it uses) into the SSR
// module graph even though <client-only> keeps it from ever rendering
// server-side. See ClientOnly.vue and vite.config.js for why that matters.
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

defineProps({
    boards: { type: Array, required: true },
});

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);
</script>
