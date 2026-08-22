<template>
    <Head :title="event.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <event-type-heading :event="event" :can-edit="canEdit">
                        <template #meta>
                            <span class="inline-flex items-center gap-1.5">
                                <u-icon name="i-lucide-grid-3x3" class="size-4 shrink-0" />
                                {{ $t('bingo.size_option', { size: card.size }) }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <u-icon name="i-lucide-trophy" class="size-4 shrink-0" />
                                {{ card.winCondition === 'FULL_HOUSE' ? $t('bingo.win_full_house') : $t('bingo.win_line') }}
                            </span>
                        </template>
                    </event-type-heading>

                    <div class="flex items-center gap-3 shrink-0">
                        <!-- Reports the live channel rather than offering a
                             refresh: a card updates itself when anyone claims
                             or a host reviews. -->
                        <span v-if="streaming" class="inline-flex items-center gap-1.5 text-xs" :class="stale ? 'text-muted' : 'text-success'">
                            <span class="relative flex size-2">
                                <span class="relative inline-flex size-2 rounded-full" :class="stale ? 'bg-muted' : 'bg-success'" />
                            </span>
                            {{ stale ? $t('events.reconnecting') : $t('events.auto_updating') }}
                        </span>

                        <span class="text-sm text-muted">
                            {{ $t('bingo.squares_done', { done: completed.length, total: squares.length }) }}
                        </span>

                        <u-button
                            :href="`/events/${event.id}/participants`"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-users-round"
                            :label="$t('participants.open')"
                        />

                        <template v-if="canEdit">
                            <!-- Three separate things, and they were one
                                 (a toggle) plus one that did not exist:
                                 quick edit changes what a SQUARE asks for,
                                 settings change what the EVENT is, and
                                 review is an admin job that belongs in its
                                 own dialog rather than in the sidebar. -->
                            <!-- "Edit tiles", not "Quick edit". The old
                                 label read as a shortcut to something,
                                 sitting next to a button called Edit board
                                 that does not touch tiles at all — so the
                                 two names described each other rather than
                                 what they do. This one is a mode: while it
                                 is on, clicking a square edits it. -->
                            <u-button
                                :color="editing ? 'primary' : 'neutral'"
                                :variant="editing ? 'solid' : 'outline'"
                                size="sm"
                                :icon="editing ? 'i-lucide-check' : 'i-lucide-grid-2x2-plus'"
                                :label="editing ? $t('bingo.editing_tiles') : $t('bingo.edit_tiles')"
                                :title="$t('bingo.edit_tiles_desc')"
                                @click="editing = !editing"
                            />

                            <u-button
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-list-checks"
                                :label="$t('tile_list.open')"
                                @click="showTileList = true"
                            />

                            <u-button
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-settings"
                                :label="$t('board.event_settings')"
                                @click="showSettingsModal = true"
                            />

                            <!-- The count lives on the button so the page
                                 says how much is waiting without spending a
                                 column on it. Colours up only when there is
                                 something to do. -->
                            <u-button
                                :color="pending.length ? 'warning' : 'neutral'"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-gavel"
                                :label="$t('bingo.review_queue')"
                                @click="showReviewModal = true"
                            >
                                <template v-if="pending.length" #trailing>
                                    <u-badge :label="String(pending.length)" color="warning" variant="solid" size="sm" />
                                </template>
                            </u-button>
                        </template>
                    </div>
                </div>

                <!-- A rejected claim used to explain itself only in the
                     square's `title` attribute — invisible on touch, and a
                     hover away from being missed anywhere else. It is the
                     one thing on this page a player has to read, so it says
                     itself, and steps through the rest when there are
                     several. -->
                <u-alert
                    v-if="visibleRejection"
                    :key="visibleRejection.position"
                    icon="i-lucide-circle-x"
                    color="error"
                    variant="subtle"
                    class="mb-6"
                    :title="$t('bingo.rejected_title', { square: visibleRejection.label })"
                    :description="visibleRejection.reviewNote || $t('bingo.rejected_no_reason')"
                    :close="true"
                    @update:open="dismissRejection"
                >
                    <template v-if="rejections.length > 1" #actions>
                        <div class="flex items-center gap-1">
                            <u-button
                                icon="i-lucide-chevron-left"
                                size="xs"
                                color="error"
                                variant="ghost"
                                :disabled="rejectionIndex === 0"
                                :aria-label="$t('bingo.rejected_previous')"
                                @click="rejectionIndex--"
                            />
                            <span class="text-xs tabular-nums text-error/90">
                                {{ rejectionIndex + 1 }} / {{ rejections.length }}
                            </span>
                            <u-button
                                icon="i-lucide-chevron-right"
                                size="xs"
                                color="error"
                                variant="ghost"
                                :disabled="rejectionIndex >= rejections.length - 1"
                                :aria-label="$t('bingo.rejected_next')"
                                @click="rejectionIndex++"
                            />
                        </div>
                    </template>
                </u-alert>

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
                        <p class="text-xs mb-2" :class="editing ? 'text-primary font-medium' : 'text-muted'">
                            {{ editing ? $t('bingo.edit_hint') : $t('bingo.mark_hint') }}
                        </p>

                        <!-- While editing, the card itself says so. The mode
                             lived only in a button at the other end of the
                             header, so the thing that had changed behaviour
                             — every square — looked identical either way.
                             A ring and a soft glow rather than a colour
                             change, so the squares' own states stay
                             readable underneath. -->
                        <div class="relative">
                            <!-- The line itself, drawn over the grid.
                                 Tinting the squares alone told you which
                                 ones without telling you they were a LINE —
                                 and on a card with several near-misses the
                                 tint could read as any of them. One stroke
                                 end to end says which, at a glance, and says
                                 it in one shape rather than five.

                                 pointer-events-none or the stroke would eat
                                 the hover that draws it. -->
                            <svg
                                v-if="lineStrokes.length"
                                class="absolute inset-0 w-full h-full pointer-events-none z-10"
                                viewBox="0 0 100 100"
                                preserveAspectRatio="none"
                                aria-hidden="true"
                            >
                                <!-- Every line through this square, not just
                                     the closest one: a centre square sits on
                                     four, and showing one of them silently
                                     hid the other three.

                                     Thin and faint. A single 6px stroke read
                                     as a decision the card had made rather
                                     than a hint about it — at 3px and 0.35
                                     it stays legible over the squares
                                     without competing with them. The nearest
                                     line is a shade stronger so "which am I
                                     closest to" still has an answer. -->
                                <line
                                    v-for="(stroke, i) in lineStrokes"
                                    :key="i"
                                    :x1="stroke.x1"
                                    :y1="stroke.y1"
                                    :x2="stroke.x2"
                                    :y2="stroke.y2"
                                    stroke="var(--ui-primary)"
                                    stroke-linecap="round"
                                    :opacity="stroke.nearest ? 0.5 : 0.22"
                                    :style="{ strokeWidth: stroke.nearest ? '3px' : '2px' }"
                                    vector-effect="non-scaling-stroke"
                                />
                            </svg>

                        <div
                            class="grid gap-2 rounded-xl transition-all duration-200"
                            :class="[gridClass, editing ? 'ring-2 ring-primary/60 shadow-[0_0_24px_-4px_var(--ui-primary)] p-2 -m-2' : '']"
                        >
                            <button
                                v-for="square in squares"
                                :key="square.id"
                                type="button"
                                class="relative aspect-square rounded-lg ring p-2 flex flex-col items-center justify-center text-center gap-2 transition-all duration-150"
                                :class="squareClass(square)"
                                :disabled="(!canPlay && !editing) || (square.isWildcard && !editing)"
                                :title="squareTitle(square)"
                                @click="onSquareClick(square)"
                                @mouseenter="hoveredPosition = square.position"
                                @mouseleave="hoveredPosition = null"
                                @focus="hoveredPosition = square.position"
                                @blur="hoveredPosition = null"
                            >
                                <!-- Points sit in the corner rather than in the
                                     label: they matter when choosing what to go
                                     for, not when reading what a square is. -->
                                <span
                                    v-if="square.points !== 1"
                                    class="absolute top-1 right-1 text-[10px] font-semibold text-muted tabular-nums"
                                >{{ square.points }}</span>

                                <!-- The icon grows when there is no label to
                                     share the square with — an unnamed square
                                     is mostly empty space, and a 24px glyph
                                     floating in it reads as an accident. -->
                                <u-icon
                                    v-if="square.isWildcard"
                                    name="i-lucide-star"
                                    class="text-warning shrink-0"
                                    :class="square.label ? 'size-5' : 'size-8'"
                                />
                                <u-icon v-else-if="statusOf(square) === 'PENDING'" name="i-lucide-clock" class="size-5 text-warning shrink-0" />
                                <u-icon v-else-if="statusOf(square) === 'REJECTED'" name="i-lucide-x" class="size-5 text-error shrink-0" />
                                <img
                                    v-else-if="square.iconUrl"
                                    :src="square.iconUrl"
                                    alt=""
                                    class="object-contain shrink-0"
                                    :class="square.label ? 'size-7' : 'size-10'"
                                />

                                <span
                                    v-if="square.label"
                                    class="text-[11px] leading-tight line-clamp-2"
                                >{{ square.label }}</span>
                                <span
                                    v-else-if="!square.iconUrl && !square.isWildcard"
                                    class="text-[11px] leading-tight text-dimmed italic"
                                >{{ $t('bingo.empty_square') }}</span>

                                <!-- Who got it. A tick told you a square was
                                     done and nothing else; on a team card the
                                     interesting part is which team, and on a
                                     solo one it is who beat you to it.
                                     Capped at three faces plus a count — see
                                     BingoService::approvedBy().

                                     `xs` rather than `3xs`: at 16px inside a
                                     square that is 90px wide you could not
                                     tell there was a face there at all, which
                                     is the whole point of putting one on. -->
                                <span v-if="holdersOf(square).length" class="flex items-center -space-x-1.5 mt-0.5">
                                    <!-- One holder gets a full-size face; a
                                         crowd steps down so three still fit
                                         across a square. `xs` was still too
                                         small to register as a person at a
                                         glance, which was the whole point. -->
                                    <u-avatar
                                        v-for="(holder, i) in holdersOf(square)"
                                        :key="i"
                                        :src="holder.avatarUrl ?? undefined"
                                        :alt="holder.name ?? ''"
                                        :title="holder.name ?? ''"
                                        :size="holdersOf(square).length === 1 ? 'md' : 'sm'"
                                        class="ring-2 ring-default"
                                    />
                                    <span
                                        v-if="extraHolders(square) > 0"
                                        class="pl-2 text-[11px] font-medium text-muted tabular-nums"
                                    >+{{ extraHolders(square) }}</span>
                                </span>
                            </button>
                        </div>
                        </div>
                    </div>

                    <div class="space-y-6">
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
                            <!-- Size, win condition and dates moved up into
                                 the heading, where they are read once rather
                                 than hunted for in a sidebar. What is left is
                                 what the heading has no room for. -->
                            <div class="space-y-2 text-sm">
                                <div v-if="card.lineBonus" class="flex items-center gap-2">
                                    <u-icon name="i-lucide-plus" class="size-4 text-muted shrink-0" />
                                    <span>{{ $t('bingo.line_bonus') }}: {{ card.lineBonus }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <u-icon :name="card.requiresApproval ? 'i-lucide-gavel' : 'i-lucide-zap'" class="size-4 text-muted shrink-0" />
                                    <span>{{ card.requiresApproval ? $t('bingo.info_reviewed') : $t('bingo.info_instant') }}</span>
                                </div>

                                <!-- The sentence above tells a host that
                                     claims wait for them; the button is
                                     where they act on it. Having the fact
                                     here and the control only in the page
                                     header made the two read as unrelated. -->
                                <u-button
                                    v-if="canEdit && card.requiresApproval"
                                    :color="pending.length ? 'warning' : 'neutral'"
                                    variant="outline"
                                    size="xs"
                                    icon="i-lucide-gavel"
                                    class="mt-1"
                                    :label="pending.length
                                        ? $t('bingo.review_pending_count', { count: pending.length })
                                        : $t('bingo.review_nothing_waiting')"
                                    @click="showReviewModal = true"
                                />
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
                :claim="claims[claimingSquare.position] ?? null"
            />
            <template v-if="canEdit">
                <board-settings-modal v-model:open="showSettingsModal" :board="event" />
                <tile-list-editor
                    v-model:open="showTileList"
                    :event-id="event.id"
                    type="BINGO"
                    :items="squares"
                    :total="card.size * card.size"
                />
                <bingo-review-modal v-model:open="showReviewModal" :event-id="event.id" :claims="pending" />
            </template>
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { boardEventStatus } from '@/Support/board';
import { openLinesThrough, strokesFor } from '@/Support/bingoLines';
import { useEventStream } from '@/Composables/useEventStream';
import ClientOnly from '@/Components/ClientOnly.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';

const BingoSquareModal = defineAsyncComponent(() => import('@/Components/BingoSquareModal.vue'));
const BingoClaimModal = defineAsyncComponent(() => import('@/Components/BingoClaimModal.vue'));
const BingoReviewModal = defineAsyncComponent(() => import('@/Components/BingoReviewModal.vue'));
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));
const TileListEditor = defineAsyncComponent(() => import('@/Components/TileListEditor.vue'));

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
    // Who holds each approved square, keyed by position — see
    // BingoService::approvedBy(). Public data: an approved claim is already
    // in the standings.
    approvedBy: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
});

// Still needed here, even though the heading renders its own copy: the live
// channel closes on an ended event rather than holding a PHP worker open to
// watch a card that cannot change.
const status = computed(() => boardEventStatus(props.event.start_date, props.event.end_date));

// Seeded from the server render, then kept current by the channel. The
// squares are streamed too, so an author's edit lands on every open card
// rather than only on the next page load.
const rows = ref([...props.standings]);
const squares = ref([...props.card.squares]);
const holders = ref({ ...props.approvedBy });
const winLines = ref(props.card.winLines ?? ['ROW', 'COLUMN', 'DIAGONAL']);

/**
 * A copy of a prop only stays right if something copies it again.
 *
 * The channel was the only thing that did, which made your OWN actions the
 * slowest ones on the page: approving a claim returns fresh props from the
 * server, but the standings on screen kept the numbers from before the
 * approval until the stream got round to saying so. Reported as approving a
 * square and being told "nobody has marked a square yet" with the counter
 * beside it reading 1 of 16.
 */
watch(() => props.standings, (value) => (rows.value = [...value]));
watch(() => props.card.squares, (value) => (squares.value = [...value]));
watch(() => props.approvedBy, (value) => (holders.value = { ...value }));
watch(() => props.card.winLines, (value) => {
    if (value) winLines.value = value;
});

function holdersOf(square) {
    return holders.value[square.position]?.holders ?? [];
}

// The count beyond the three faces shown, or 0.
function extraHolders(square) {
    const entry = holders.value[square.position];

    return entry ? Math.max(0, entry.total - entry.holders.length) : 0;
}

const { streaming, stale } = useEventStream({
    // A finished card cannot change. Holding a PHP worker open to watch it
    // not change is the one cost this feature has.
    url: () => (status.value === 'ended' ? null : `/events/${props.event.id}/stream`),
    event: 'bingo',
    onMessage: (payload) => {
        rows.value = payload.standings;
        if (payload.squares) squares.value = payload.squares;
        if (payload.approvedBy) holders.value = payload.approvedBy;
        // A host changing which shapes count mid-event reaches every open
        // card, so the hint never points at a line that stopped counting.
        if (payload.winLines) winLines.value = payload.winLines;

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

/**
 * The square the pointer is on, and the squares that would finish a line
 * through it.
 *
 * Bingo is a game about lines, and the card showed none of them until one
 * was already complete — so "what do I still need" was a question you
 * answered by counting rows with your finger. Hovering a square now lights
 * up every square on its best unfinished line.
 *
 * "Best" = the line it belongs to that you are closest to finishing. A
 * square in the middle of a 5x5 sits on four lines, and highlighting all
 * four lights up most of the card, which says nothing.
 */
const hoveredPosition = ref(null);

const mine = computed(() => new Set(props.completed));

/**
 * The line the hovered square would finish, as positions.
 *
 * Silent while editing: in that mode a click changes what a square ASKS for,
 * and a hint about completing lines is answering a question nobody is asking
 * — it also fights the ring that marks the mode.
 */
const suggestedPositions = computed(() => {
    if (editing.value || hoveredPosition.value === null) return [];

    return openLinesThrough(hoveredPosition.value, props.card.size, mine.value, winLines.value);
});

// The squares to tint: every square on every candidate line, minus what you
// already hold and minus the one under the pointer, which has its own hover
// state.
const suggestedLine = computed(() => new Set(
    suggestedPositions.value.flat().filter((p) => !mine.value.has(p) && p !== hoveredPosition.value),
));

// One stroke per candidate line, nearest-to-finishing marked — see
// Support/bingoLines.js, which is also where the server's own definition of
// a line is mirrored.
const lineStrokes = computed(() => strokesFor(suggestedPositions.value, props.card.size, mine.value));

function squareClass(square) {
    // Part of the line the hovered square would complete. Checked before
    // everything else so the hint is visible on a square whose own state
    // would otherwise paint over it.
    if (suggestedLine.value.has(square.position)) {
        return 'ring-primary bg-primary/10 text-highlighted';
    }

    // A free square is already everyone's, so it reads as done from the
    // start — and distinctly from a square you completed, or the fact that
    // it costs nothing to have is invisible.
    if (square.isWildcard) {
        return 'ring-warning/50 bg-warning/10 text-highlighted';
    }

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
    if (square.isWildcard) return trans('bingo.wildcard_desc');
    if (editing.value) return trans('bingo.edit_tiles_desc');

    // A claimed square says a click opens it rather than what the verdict
    // was — the verdict is on the square already, and the reason lives in
    // the dialog where there is room for it.
    if (statusOf(square)) return trans('bingo.open_claim');

    return square.label ?? '';
}

/**
 * This viewer's rejected claims, worst-case several at once.
 *
 * Dismissals are held in a Set of positions rather than removing rows, so a
 * claim that is rejected again after a re-submission comes back — the point
 * of the notice is that you find out, and "I closed it once" is not the same
 * as "I know".
 */
const dismissedRejections = ref(new Set());

const rejections = computed(() => squares.value
    .filter((square) => props.claims[square.position]?.status === 'REJECTED')
    .filter((square) => !dismissedRejections.value.has(square.position))
    .map((square) => ({
        position: square.position,
        label: square.label || trans('bingo.square_number', { n: square.position + 1 }),
        reviewNote: props.claims[square.position]?.reviewNote ?? null,
    })));

const rejectionIndex = ref(0);

const visibleRejection = computed(() => rejections.value[Math.min(rejectionIndex.value, rejections.value.length - 1)] ?? null);

function dismissRejection() {
    if (!visibleRejection.value) return;

    const next = new Set(dismissedRejections.value);
    next.add(visibleRejection.value.position);
    dismissedRejections.value = next;
    rejectionIndex.value = 0;
}

const editing = ref(false);
// Opened automatically on ?setup=tiles — the redirect BoardController::store
// sends a brand-new event to. onMounted rather than at setup, because
// `location` does not exist during SSR.
const showTileList = ref(false);
onMounted(() => {
    if (props.canEdit && new URLSearchParams(window.location.search).get('setup') === 'tiles') {
        showTileList.value = true;
    }
});
const showSettingsModal = ref(false);
const showReviewModal = ref(false);
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

    // Free to everyone, so there is nothing to claim or withdraw. The server
    // refuses it too; this just stops the click from looking like it did
    // something.
    if (square.isWildcard) return;

    if (!props.canPlay) return;

    // An existing claim always opens the dialog, whatever the card's review
    // setting. Withdrawing used to happen on a bare second click — no hover
    // state saying so, nothing confirming it — so a stray click quietly
    // undid a claim and, on a reviewed card, its place in the queue.
    if (statusOf(square)) {
        claimingSquare.value = square;
        claimModalOpen.value = true;

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
</script>
