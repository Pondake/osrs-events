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

                <!-- One per row rather than the public grid: these are events
                     you're actually playing, so the useful thing is the state of
                     each — where you are, what's ahead — not how many exist. The
                     preview is read-only on purpose; it shows the board's shape
                     without pretending to be the board. -->
                <div v-else class="space-y-4">
                    <div
                        v-for="entry in boards"
                        :key="entry.board.id"
                        class="flex flex-col lg:flex-row gap-5 rounded-lg ring ring-default bg-default p-5"
                    >
                        <div class="flex-1 min-w-0 flex flex-col">
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div class="min-w-0">
                                    <a :href="`/events/${entry.board.id}`" class="text-lg font-semibold text-highlighted hover:text-primary transition-colors">
                                        {{ entry.board.title }}
                                    </a>
                                    <p v-if="entry.board.description" class="text-sm text-muted mt-0.5 line-clamp-2">{{ entry.board.description }}</p>
                                </div>
                                <u-badge
                                    :label="statusLabel(entry.board)"
                                    :color="statusColor(entry.board)"
                                    variant="subtle"
                                    size="sm"
                                    class="shrink-0"
                                />
                            </div>

                            <div class="flex items-center gap-x-4 gap-y-1 flex-wrap mt-3 text-xs text-muted">
                                <span v-if="entry.kind === 'board'" class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-grid-3x3" class="size-3.5" />
                                    {{ sizeLabel(entry.board.size) }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-trophy" class="size-3.5" />
                                    {{ $t('events.ranked_by', { skill: $t(`skills.${entry.board.metric}`) }) }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-calendar" class="size-3.5" />
                                    {{ dateRange(entry.board) }}
                                </span>
                            </div>

                            <!-- A board has a position to advance; a race has a
                                 placing among others. Neither reads as the
                                 other, so they do not share a widget. -->
                            <div v-if="entry.kind === 'board'" class="mt-4">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-muted">{{ $t('boards.progress_tile', { current: entry.progress.current, total: entry.progress.total }) }}</span>
                                    <span class="text-highlighted tabular-nums">{{ entry.progress.pct }}%</span>
                                </div>
                                <u-progress :model-value="entry.progress.pct" size="sm" />
                            </div>

                            <div v-else class="mt-4 flex items-center gap-4 flex-wrap">
                                <div v-if="entry.standing.rank" class="flex items-baseline gap-1.5">
                                    <span class="text-2xl font-bold text-primary tabular-nums">#{{ entry.standing.rank }}</span>
                                    <span class="text-xs text-muted">{{ $t('events.of_participants', { count: entry.standing.participants }) }}</span>
                                </div>
                                <span v-else-if="entry.standing.error" class="text-sm text-muted inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-circle-help" class="size-4" />
                                    {{ $t(`events.error_${entry.standing.error}`) }}
                                </span>
                                <span v-else class="text-sm text-muted">{{ $t('events.pending_sync') }}</span>

                                <span v-if="entry.standing.syncedAt && !entry.standing.error" class="text-sm text-highlighted tabular-nums">
                                    +{{ formatXp(entry.standing.gained) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-default">
                                <u-button
                                    :href="`/events/${entry.board.id}`"
                                    size="sm"
                                    color="primary"
                                    :icon="entry.kind === 'board' ? 'i-lucide-play' : 'i-lucide-trophy'"
                                    :label="entry.kind === 'board' ? $t('boards.continue') : $t('events.view_standings')"
                                />
                                <u-button
                                    v-if="entry.kind === 'board'"
                                    :href="`/events/${entry.board.id}/leaderboard`"
                                    size="sm"
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-trophy"
                                    :label="$t('boards.leaderboard')"
                                />
                            </div>
                        </div>

                        <!-- Capped when it stacks: below lg this sat full width,
                             which turned a 9x9 grid into most of the screen.
                             A race has no board to preview at all. -->
                        <div v-if="entry.kind === 'board'" class="w-full max-w-64 mx-auto lg:mx-0 lg:w-64 shrink-0">
                            <board-preview
                                :size="entry.preview.size"
                                :special-tiles="entry.preview.specialTiles"
                                :current-position="entry.preview.currentPosition"
                                :completed-positions="entry.preview.completedPositions"
                            />
                        </div>
                    </div>
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
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import BoardPreview from '@/Components/BoardPreview.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT, boardEventStatus, formatDate } from '@/Support/board';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

defineProps({
    // Two shapes, told apart by `kind`:
    //   board - { kind, board, progress: { current, total, pct }, preview }
    //   race  - { kind, board, standing: { rank, gained, syncedAt, error, participants } }
    boards: { type: Array, required: true },
});

// Grouped thousands, same as the standings page: XP gains run into the
// millions and an unbroken run of digits cannot be read at a glance.
function formatXp(value) {
    return new Intl.NumberFormat('en-GB').format(value ?? 0);
}

function sizeLabel(size) {
    return trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size] ?? size, tiles: BOARD_TILE_COUNT[size] ?? '?' });
}

// Same UTC-day comparison the cards use (Support/board.js), so a board never
// reads as Live on one page and Ended on another.
function statusLabel(board) {
    return trans(`boards.status_${boardEventStatus(board.start_date, board.end_date)}`);
}

const STATUS_COLOR = { upcoming: 'info', live: 'success', ended: 'neutral' };

function statusColor(board) {
    return STATUS_COLOR[boardEventStatus(board.start_date, board.end_date)] ?? 'neutral';
}

function dateRange(board) {
    if (!board.start_date && !board.end_date) return trans('boards.no_dates');

    return `${formatDate(board.start_date)} – ${formatDate(board.end_date)}`;
}

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);
</script>
