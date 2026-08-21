<template>
    <Head :title="event.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <u-badge
                                v-if="typeMeta"
                                :icon="typeMeta.icon"
                                :label="typeMeta.label"
                                color="primary"
                                variant="subtle"
                                size="sm"
                            />
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
                        <!-- Reports the live channel rather than offering a
                             refresh: a card updates itself when anyone claims
                             or a host reviews. -->
                        <span v-if="streaming" class="inline-flex items-center gap-1.5 text-xs" :class="stale ? 'text-muted' : 'text-success'">
                            <span class="relative flex size-2">
                                <span v-if="!stale" class="absolute inline-flex size-full rounded-full bg-success opacity-60 animate-ping" />
                                <span class="relative inline-flex size-2 rounded-full" :class="stale ? 'bg-muted' : 'bg-success'" />
                            </span>
                            {{ stale ? $t('events.reconnecting') : $t('events.live') }}
                        </span>

                        <span class="text-sm text-muted">
                            {{ $t('bingo.squares_done', { done: completed.length, total: squares.length }) }}
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
                     look, but a claim would score against nobody. -->
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
                                v-for="square in squares"
                                :key="square.id"
                                type="button"
                                class="relative aspect-square rounded-lg ring p-2 flex flex-col items-center justify-center text-center gap-1 transition-colors"
                                :class="squareClass(square)"
                                :disabled="!canPlay && !editing"
                                :title="squareTitle(square)"
                                @click="onSquareClick(square)"
                            >
                                <!-- Points sit in the corner rather than in the
                                     label: they matter when choosing what to go
                                     for, not when reading what a square is. -->
                                <span
                                    v-if="square.points !== 1"
                                    class="absolute top-1 right-1 text-[10px] font-semibold text-muted tabular-nums"
                                >{{ square.points }}</span>

                                <u-icon v-if="statusOf(square) === 'APPROVED'" name="i-lucide-check" class="size-5 text-success shrink-0" />
                                <u-icon v-else-if="statusOf(square) === 'PENDING'" name="i-lucide-clock" class="size-5 text-warning shrink-0" />
                                <u-icon v-else-if="statusOf(square) === 'REJECTED'" name="i-lucide-x" class="size-5 text-error shrink-0" />
                                <img v-else-if="square.iconUrl" :src="square.iconUrl" alt="" class="size-6 object-contain" />

                                <span class="text-[11px] leading-tight line-clamp-3" :class="square.label ? '' : 'text-dimmed italic'">
                                    {{ square.label || $t('bingo.empty_square') }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- The review queue sits beside the card, not on
                             another screen: leaving the thing you are judging
                             in order to judge it is the wrong shape. -->
                        <u-card v-if="canEdit" :ui="{ body: 'p-0 sm:p-0' }">
                            <template #header>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $t('bingo.pending_queue') }}</span>
                                    <u-badge v-if="pending.length" :label="String(pending.length)" color="warning" variant="subtle" size="sm" />
                                </div>
                            </template>

                            <ul v-if="pending.length" class="divide-y divide-default">
                                <li v-for="claim in pending" :key="claim.id" class="px-4 py-3 space-y-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate">{{ claim.label || $t('bingo.empty_square') }}</p>
                                        <p class="text-xs text-muted truncate">
                                            {{ claim.competitor }} · {{ $t('bingo.submitted_by', { name: claim.submittedBy }) }}
                                        </p>
                                        <p v-if="claim.note" class="text-xs text-muted mt-1">{{ claim.note }}</p>
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap">
                                        <u-button
                                            v-if="claim.proofUrl"
                                            :href="claim.proofUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            size="xs"
                                            color="neutral"
                                            variant="outline"
                                            icon="i-lucide-image"
                                            :label="$t('bingo.view_proof')"
                                        />
                                        <span v-else class="text-xs text-muted italic">{{ $t('bingo.no_proof') }}</span>

                                        <div class="ms-auto flex items-center gap-1">
                                            <u-button size="xs" color="success" variant="soft" :label="$t('bingo.approve')" @click="review(claim, 'APPROVED')" />
                                            <u-button size="xs" color="error" variant="soft" :label="$t('bingo.reject')" @click="review(claim, 'REJECTED')" />
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <p v-else class="px-4 py-8 text-center text-sm text-muted">{{ $t('bingo.no_pending') }}</p>
                        </u-card>

                        <u-card :ui="{ body: 'p-0 sm:p-0' }">
                            <template #header>
                                <span class="font-semibold">{{ $t('bingo.standings') }}</span>
                            </template>

                            <ul v-if="rows.length" class="divide-y divide-default">
                                <li v-for="(row, index) in rows" :key="row.id" class="flex items-center gap-3 px-4 py-3">
                                    <span class="w-6 text-sm font-semibold tabular-nums" :class="index < 3 ? 'text-primary' : 'text-muted'">
                                        {{ index + 1 }}
                                    </span>
                                    <u-avatar :src="row.avatarUrl ?? undefined" :alt="row.name" size="sm" />
                                    <div class="flex-1 min-w-0">
                                        <p class="truncate">{{ row.name }}</p>
                                        <p class="text-xs text-muted">
                                            {{ $t('bingo.lines_done', { count: row.lines }) }} · {{ row.squares }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-medium text-highlighted tabular-nums shrink-0">
                                        {{ $t('bingo.score', { points: row.points }) }}
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
                                <div v-if="card.lineBonus" class="flex items-center gap-2">
                                    <u-icon name="i-lucide-plus" class="size-4 text-muted shrink-0" />
                                    <span>{{ $t('bingo.line_bonus') }}: {{ card.lineBonus }}</span>
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
            <bingo-claim-modal
                v-if="claimingSquare"
                v-model:open="claimModalOpen"
                :event-id="event.id"
                :square="claimingSquare"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { boardEventStatus, formatDate } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';
import { useEventStream } from '@/Composables/useEventStream';
import ClientOnly from '@/Components/ClientOnly.vue';

const BingoSquareModal = defineAsyncComponent(() => import('@/Components/BingoSquareModal.vue'));
const BingoClaimModal = defineAsyncComponent(() => import('@/Components/BingoClaimModal.vue'));

const props = defineProps({
    event: { type: Object, required: true },
    card: { type: Object, required: true },
    claims: { type: Object, default: () => ({}) },
    completed: { type: Array, default: () => [] },
    completedLines: { type: Array, default: () => [] },
    hasWon: { type: Boolean, default: false },
    canPlay: { type: Boolean, default: false },
    standings: { type: Array, default: () => [] },
    pending: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
});

const typeMeta = computed(() => eventTypeMeta(props.event.type));

const status = computed(() => boardEventStatus(props.event.start_date, props.event.end_date));

const STATUS_COLOR = { upcoming: 'info', live: 'success', ended: 'neutral' };
const statusColor = computed(() => STATUS_COLOR[status.value] ?? 'neutral');

const dateRange = computed(() => {
    if (!props.event.start_date && !props.event.end_date) return trans('boards.no_dates');

    return `${formatDate(props.event.start_date)} – ${formatDate(props.event.end_date)}`;
});

// Seeded from the server render, then kept current by the channel. The
// squares are streamed too, so an author's edit lands on every open card
// rather than only on the next page load.
const rows = ref([...props.standings]);
const squares = ref([...props.card.squares]);

const { streaming, stale } = useEventStream({
    // A finished card cannot change. Holding a PHP worker open to watch it
    // not change is the one cost this feature has.
    url: () => (status.value === 'ended' ? null : `/events/${props.event.id}/stream`),
    event: 'bingo',
    onMessage: (payload) => {
        rows.value = payload.standings;
        if (payload.squares) squares.value = payload.squares;

        // The review queue is host-only, so it cannot ride a channel every
        // viewer shares. The stream still says *that* something changed, so a
        // host re-fetches just that prop — which is also what refreshes their
        // own claim states after somebody else's claim is ruled on.
        if (props.canEdit) {
            router.reload({ only: ['pending', 'claims', 'completed', 'completedLines', 'hasWon'] });
        }
    },
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
    8: 'grid-cols-8',
    9: 'grid-cols-9',
    10: 'grid-cols-10',
};
const gridClass = computed(() => GRID_CLASSES[props.card.size] ?? GRID_CLASSES[5]);

const inLine = computed(() => new Set(props.completedLines.flat()));

function statusOf(square) {
    return props.claims[square.position]?.status ?? null;
}

function squareClass(square) {
    const state = statusOf(square);

    // Four states, not two. A pending claim is visibly different from an
    // approved one, or a player cannot tell whether a host has looked yet.
    if (state === 'APPROVED') {
        return inLine.value.has(square.position)
            ? 'ring-success bg-success/20 text-highlighted'
            : 'ring-success/40 bg-success/10';
    }

    if (state === 'PENDING') return 'ring-warning/50 bg-warning/10';
    if (state === 'REJECTED') return 'ring-error/40 bg-error/5';

    return 'ring-default bg-default hover:ring-primary';
}

function squareTitle(square) {
    const state = statusOf(square);

    if (state === 'PENDING') return trans('bingo.status_pending');
    if (state === 'REJECTED') return props.claims[square.position]?.reviewNote || trans('bingo.status_rejected');

    return square.label ?? '';
}

const editing = ref(false);
const squareModalOpen = ref(false);
const editingSquare = ref(null);
const claimModalOpen = ref(false);
const claimingSquare = ref(null);

function onSquareClick(square) {
    if (editing.value) {
        editingSquare.value = square;
        squareModalOpen.value = true;

        return;
    }

    if (!props.canPlay) return;

    // Withdrawing a claim needs no proof, so it does not need the dialog —
    // only making one does.
    if (statusOf(square)) {
        router.post(`/events/${props.event.id}/bingo/squares/${square.id}/claim`, {}, {
            preserveScroll: true,
            onError: (errors) => console.error(errors),
        });

        return;
    }

    // A card that trusts its players marks straight away; one that reviews
    // asks for the screenshot at the only moment the player still has it.
    if (!props.card.requiresApproval) {
        router.post(`/events/${props.event.id}/bingo/squares/${square.id}/claim`, {}, {
            preserveScroll: true,
            onError: (errors) => console.error(errors),
        });

        return;
    }

    claimingSquare.value = square;
    claimModalOpen.value = true;
}

function review(claim, status) {
    router.patch(`/events/${props.event.id}/bingo/claims/${claim.id}`, { status }, {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
    });
}
</script>
