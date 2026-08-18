<template>
    <Head title="Boards" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <h1 class="text-3xl font-bold text-highlighted">Boards</h1>

                    <u-button
                        v-if="canCreateBoards"
                        color="primary"
                        icon="i-lucide-plus"
                        label="Create board"
                        @click="showCreateModal = true"
                    />
                </div>

                <div v-if="!boards.length" class="text-center py-16">
                    <u-icon name="i-lucide-layout-grid" class="size-12 text-muted mx-auto mb-4" />
                    <p class="text-lg font-medium">No boards yet</p>
                    <p class="text-sm text-muted mt-1">Boards you create or join will show up here.</p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="{ board, status, access } in decoratedBoards" :key="board.id" :href="`/boards/${board.id}`">
                        <u-page-card class="h-full hover:border-primary transition-colors cursor-pointer" :ui="{ body: 'w-full' }">
                            <template #title>
                                <div class="flex items-center justify-between gap-3 w-full">
                                    <span class="truncate">{{ board.title }}</span>
                                    <div class="flex items-center gap-1.5 text-xs font-medium rounded-md px-2 py-1 shrink-0" :class="status.class">
                                        <u-icon :name="status.icon" class="size-3.5" />
                                        <span>{{ status.label }}</span>
                                    </div>
                                </div>
                            </template>

                            <template #description>
                                <div class="flex flex-col gap-2 mt-2">
                                    <div class="flex items-center gap-2 text-sm text-muted">
                                        <u-icon name="i-lucide-calendar" class="size-4" />
                                        <span>{{ formatDate(board.start_date) }} – {{ formatDate(board.end_date) }}</span>
                                    </div>

                                    <div class="flex items-center gap-2 text-sm text-muted">
                                        <u-icon name="i-lucide-grid-3x3" class="size-4" />
                                        <span>{{ formatBoardSize(board.size) }} board</span>
                                    </div>

                                    <div v-if="board.dice_roll_limit" class="flex items-center gap-2 text-sm text-muted">
                                        <u-icon name="i-lucide-dice-6" class="size-4" />
                                        <span>{{ board.dice_roll_limit }} rolls/day</span>
                                    </div>

                                    <div v-if="access" class="flex items-center gap-2 text-sm text-muted">
                                        <u-icon :name="access.icon" class="size-4" />
                                        <span>{{ access.label }}</span>
                                    </div>

                                    <div v-if="board.mode === 'TEAM'" class="flex items-center gap-2 text-sm text-muted">
                                        <u-icon name="i-lucide-users" class="size-4" />
                                        <span>Team mode</span>
                                    </div>

                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex -space-x-2">
                                            <u-avatar
                                                v-for="author in board.authors.slice(0, 3)"
                                                :key="author.id"
                                                :src="author.user.avatar_url ?? undefined"
                                                :alt="author.user.nickname ?? author.user.discord_username"
                                                size="xs"
                                                class="ring-2 ring-background"
                                            />
                                        </div>
                                        <span class="text-xs text-muted">
                                            {{ board.authors.map((a) => a.user.nickname ?? a.user.discord_username).join(', ') }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template #footer>
                                <u-button variant="ghost" color="primary" trailing-icon="i-lucide-arrow-right" size="sm" label="Play" />
                            </template>
                        </u-page-card>
                    </Link>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal v-model:open="showCreateModal" :board="null" />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import { formatBoardSize, formatDate, boardEventStatus, BOARD_ACCESS_META, BOARD_STATUS_STYLE } from '@/Support/board';
import { defineAsyncComponent } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';

// Dynamic import, not a static one — a static import would still pull
// BoardSettingsModal (and the @nuxt/ui form components it uses) into the SSR
// module graph even though <client-only> keeps it from ever rendering
// server-side. See ClientOnly.vue and vite.config.js for why that matters.
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const props = defineProps({
    boards: { type: Array, required: true },
});

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);

const decoratedBoards = computed(() =>
    props.boards.map((board) => ({
        board,
        status: BOARD_STATUS_STYLE[boardEventStatus(board.start_date, board.end_date)],
        access: board.access_mode ? BOARD_ACCESS_META[board.access_mode] : undefined,
    })),
);
</script>
