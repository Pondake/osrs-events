<template>
    <Head :title="event.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <u-badge :label="$t('events.type_bingo')" color="primary" variant="subtle" size="sm" />
                            <u-badge :label="$t(`boards.status_${status}`)" :color="statusColor" variant="subtle" size="sm" />
                            <u-badge
                                :label="card.winCondition === 'FULL_HOUSE' ? $t('bingo.win_full_house') : $t('bingo.win_line')"
                                color="neutral"
                                variant="subtle"
                                size="sm"
                            />
                        </div>
                        <h1 class="text-3xl font-bold text-highlighted mt-2">{{ event.title }}</h1>
                        <p v-if="event.description" class="text-muted mt-1">{{ event.description }}</p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-sm text-muted">
                            {{ $t('bingo.squares_done', { done: completed.length, total: card.squares.length }) }}
                        </span>

                        <!-- An author can both play and edit, and one click
                             cannot mean both — so which it means is an
                             explicit mode rather than a guess. -->
                        <u-button
                            v-if="canEdit"
                            :color="editing ? 'primary' : 'neutral'"
                            :variant="editing ? 'solid' : 'outline'"
                            size="sm"
                            icon="i-lucide-pencil"
                            :label="$t('bingo.edit_mode')"
                            @click="editing = !editing"
                        />
                    </div>
                </div>

                <u-alert
                    v-if="hasWon"
                    icon="i-lucide-party-popper"
                    color="success"
                    variant="subtle"
                    class="mb-6"
                    :title="$t('bingo.you_won')"
                />

                <!-- A TEAM event where you are on no assigned team: you can
                     look, but a click would score against nobody. Said plainly
                     rather than leaving dead squares. -->
                <u-alert
                    v-else-if="!canPlay && !canEdit"
                    icon="i-lucide-users"
                    color="warning"
                    variant="subtle"
                    class="mb-6"
                    :title="$t('bingo.no_team')"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <p class="text-xs text-muted mb-2">{{ editing ? $t('bingo.edit_hint') : $t('bingo.mark_hint') }}</p>

                        <div class="grid gap-2" :class="gridClass">
                            <button
                                v-for="square in card.squares"
                                :key="square.id"
                                type="button"
                                class="aspect-square rounded-lg ring p-2 flex flex-col items-center justify-center text-center gap-1 transition-colors"
                                :class="squareClass(square)"
                                :disabled="!canPlay && !editing"
                                @click="onSquareClick(square)"
                            >
                                <u-icon v-if="isDone(square)" name="i-lucide-check" class="size-5 text-success shrink-0" />
                                <img v-else-if="square.iconUrl" :src="square.iconUrl" alt="" class="size-6 object-contain" />

                                <span class="text-[11px] leading-tight line-clamp-3" :class="square.label ? '' : 'text-dimmed italic'">
                                    {{ square.label || $t('bingo.empty_square') }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <u-card :ui="{ body: 'p-0 sm:p-0' }">
                            <template #header>
                                <span class="font-semibold">{{ $t('bingo.standings') }}</span>
                            </template>

                            <ul v-if="standings.length" class="divide-y divide-default">
                                <li v-for="(row, index) in standings" :key="row.id" class="flex items-center gap-3 px-4 py-3">
                                    <span class="w-6 text-sm font-semibold tabular-nums" :class="index < 3 ? 'text-primary' : 'text-muted'">
                                        {{ index + 1 }}
                                    </span>
                                    <u-avatar :src="row.avatarUrl ?? undefined" :alt="row.name" size="sm" />
                                    <span class="flex-1 min-w-0 truncate">{{ row.name }}</span>
                                    <span class="text-xs text-muted shrink-0">
                                        {{ $t('bingo.lines_done', { count: row.lines }) }} · {{ row.squares }}
                                    </span>
                                    <u-icon v-if="row.won" name="i-lucide-trophy" class="size-4 text-warning shrink-0" />
                                </li>
                            </ul>

                            <p v-else class="px-4 py-10 text-center text-sm text-muted">{{ $t('bingo.no_standings') }}</p>
                        </u-card>

                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('board.information') }}</span>
                            </template>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <u-icon name="i-lucide-grid-3x3" class="size-4 text-muted shrink-0" />
                                    <span>{{ $t('bingo.size_option', { size: card.size }) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <u-icon name="i-lucide-calendar" class="size-4 text-muted shrink-0" />
                                    <span>{{ dateRange }}</span>
                                </div>
                            </div>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>

        <!-- ClientOnly + async, like every other modal here: u-modal reaches
             the '#imports' virtual specifier that breaks the SSR build. -->
        <client-only>
            <bingo-square-modal
                v-if="editingSquare"
                v-model:open="squareModalOpen"
                :event-id="event.id"
                :square="editingSquare"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { boardEventStatus, formatDate } from '@/Support/board';
import ClientOnly from '@/Components/ClientOnly.vue';

const BingoSquareModal = defineAsyncComponent(() => import('@/Components/BingoSquareModal.vue'));

const props = defineProps({
    event: { type: Object, required: true },
    card: { type: Object, required: true },
    completed: { type: Array, default: () => [] },
    completedLines: { type: Array, default: () => [] },
    hasWon: { type: Boolean, default: false },
    canPlay: { type: Boolean, default: false },
    standings: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
});

const status = computed(() => boardEventStatus(props.event.start_date, props.event.end_date));

const STATUS_COLOR = { upcoming: 'info', live: 'success', ended: 'neutral' };
const statusColor = computed(() => STATUS_COLOR[status.value] ?? 'neutral');

const dateRange = computed(() => {
    if (!props.event.start_date && !props.event.end_date) return trans('boards.no_dates');

    return `${formatDate(props.event.start_date)} – ${formatDate(props.event.end_date)}`;
});

// Written out per size rather than built as `grid-cols-${n}` — Tailwind scans
// source text, so an interpolated class name is never generated and the grid
// would collapse to one column.
const GRID_CLASSES = {
    3: 'grid-cols-3',
    4: 'grid-cols-4',
    5: 'grid-cols-5',
    6: 'grid-cols-6',
    7: 'grid-cols-7',
};
const gridClass = computed(() => GRID_CLASSES[props.card.size] ?? GRID_CLASSES[5]);

const done = computed(() => new Set(props.completed));
const inLine = computed(() => new Set(props.completedLines.flat()));

function isDone(square) {
    return done.value.has(square.position);
}

function squareClass(square) {
    if (!isDone(square)) return 'ring-default bg-default hover:ring-primary';

    // A square that completes a line reads differently from one that is only
    // ticked — the line is the point of the game, so it belongs on the board
    // rather than only in a banner.
    return inLine.value.has(square.position)
        ? 'ring-success bg-success/20 text-highlighted'
        : 'ring-success/40 bg-success/10';
}

const editing = ref(false);
const squareModalOpen = ref(false);
const editingSquare = ref(null);

function onSquareClick(square) {
    if (editing.value) {
        editingSquare.value = square;
        squareModalOpen.value = true;

        return;
    }

    if (!props.canPlay) return;

    router.post(`/events/${props.event.id}/bingo/squares/${square.id}/toggle`, {}, {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
    });
}
</script>
