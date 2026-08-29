<template>
    <Head :title="$t('boards.mine_title')">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <!-- Stacks on a phone. Side by side, the title block was left
                     with a ~150px column and the description broke into
                     four ragged lines beside a button that needed none of
                     the room it was taking. -->
                <div class="flex flex-col items-start gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
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

                <!-- Links, not a client-side toggle: each one is a real URL
                     the hub can point at, and it survives a refresh. -->
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <u-button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        :href="tab.href"
                        size="sm"
                        :color="filter === tab.key ? 'primary' : 'neutral'"
                        :variant="filter === tab.key ? 'solid' : 'outline'"
                        :label="`${tab.label} (${tab.count})`"
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
                                <!-- Same lockup the detail pages use, at the
                                     same size — a pill with a real icon and
                                     a coloured status dot. It was two `sm`
                                     badges, which is the size you use for
                                     metadata, not for the two facts the row
                                     exists to tell you. -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <span
                                        v-if="typeMeta(entry.board)"
                                        class="inline-flex items-center gap-2 rounded-full bg-primary/10 ring-1 ring-primary/30 pl-2 pr-3 py-1"
                                    >
                                        <u-icon :name="typeMeta(entry.board).icon" class="size-4 text-primary shrink-0" />
                                        <span class="text-sm font-medium text-primary">{{ typeMeta(entry.board).label }}</span>
                                    </span>

                                    <span class="inline-flex items-center gap-2">
                                        <span class="size-2.5 rounded-full" :class="statusDot(entry.board)" />
                                        <span class="text-sm font-medium" :class="statusText(entry.board)">{{ statusLabel(entry.board) }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-x-4 gap-y-1 flex-wrap mt-3 text-xs text-muted">
                                <span v-if="entry.kind === 'board' && entry.board.size" class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-grid-3x3" class="size-3.5" />
                                    {{ sizeLabel(entry.board.size) }}
                                </span>
                                <!-- Bingo had no entry in this row at all —
                                     a board said its grid size, a race said
                                     what it ranked on, and a bingo event said
                                     nothing, which is the same "one type
                                     quietly does less" gap the preview slot
                                     had. -->
                                <span v-else-if="entry.kind === 'bingo' && entry.board.bingo_size" class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-grid-3x3" class="size-3.5" />
                                    {{ $t('boards.bingo_card', { size: entry.board.bingo_size }) }}
                                </span>
                                <span v-else-if="entry.kind === 'race'" class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-trophy" class="size-3.5" />
                                    {{ rankedBy(entry.board) }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-calendar" class="size-3.5" />
                                    {{ dateRange(entry.board) }}
                                </span>
                                <!-- The one fact every kind has, unlike the
                                     kind-specific one before it — a race
                                     already says this inside RacePreview
                                     once you've entered, but a host-only
                                     entry (any kind) had nowhere on the row
                                     saying how many people are actually in
                                     it. -->
                                <span v-if="entry.participants" class="inline-flex items-center gap-1">
                                    <u-icon name="i-lucide-users" class="size-3.5" />
                                    {{ $t('participants.count', { count: entry.participants }) }}
                                </span>
                            </div>

                            <!-- A board has a position to advance — a race's
                                 equivalent (rank, participants, XP gained) now
                                 lives in RacePreview instead, in the same slot
                                 every other event kind uses for its preview.
                                 It used to be repeated here too, which is
                                 exactly the "same fact, two places, laid out
                                 differently each time" this pass was about. -->
                            <div v-if="entry.progress" class="mt-4">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-muted">{{ $t('boards.progress_tile', { current: entry.progress.current, total: entry.progress.total }) }}</span>
                                    <span class="text-highlighted tabular-nums">{{ entry.progress.pct }}%</span>
                                </div>
                                <u-progress :model-value="entry.progress.pct" size="sm" />
                            </div>

                            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-default">
                                <!-- One label, one icon, regardless of kind.
                                     "Continue" vs "Play" vs "View standings"
                                     all went to the exact same href — the
                                     wording differed, the destination never
                                     did, so the extra words were saying
                                     something about the row that wasn't
                                     actually true of it: three kinds of
                                     button, one kind of action. -->
                                <u-button
                                    :href="`/events/${entry.board.id}`"
                                    size="sm"
                                    color="primary"
                                    icon="i-lucide-arrow-right"
                                    :label="$t('common.open')"
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

                        <!-- Every entry gets this slot now, whatever its
                             kind — a race used to have nothing beside it
                             while a board or bingo entry got a real preview,
                             which made the same page read as three different
                             layouts wearing one wrapper. One container size
                             (`w-64`, was `w-64` for a board and `w-56` for
                             bingo — two more numbers that had no reason to
                             differ) and exactly one of the three below
                             always renders into it.

                             Capped when it stacks: below lg this sat full
                             width, which turned a 9x9 grid into most of the
                             screen.

                             A real `Link`, not a decorative box: the board
                             preview reuses BoardPreview's tile styling
                             (app.css's `.board-tile`, including its hover
                             scale-up), which is built for a real, clickable
                             board — so it already LOOKED interactive here
                             even though nothing happened when you clicked
                             it. Reported directly. Rather than strip the
                             hover cue, made the thing it was promising
                             true: the whole preview now goes to the same
                             place the title and the Open button do. -->
                        <Link
                            :href="`/events/${entry.board.id}`"
                            class="block w-full max-w-64 mx-auto lg:mx-0 lg:w-64 shrink-0 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <!-- Played: the real board, your real position.
                                 Hosted but not played: BoardPreview's own
                                 illustrative mode (no specialTiles/position
                                 given) — the same placeholder the create-event
                                 form shows, rather than an empty box. -->
                            <board-preview
                                v-if="entry.kind === 'board' && entry.preview"
                                :size="entry.preview.size"
                                :special-tiles="entry.preview.specialTiles"
                                :current-position="entry.preview.currentPosition"
                                :completed-positions="entry.preview.completedPositions"
                            />
                            <board-preview v-else-if="entry.kind === 'board'" :size="entry.board.size" />

                            <bingo-preview v-else-if="entry.kind === 'bingo' && entry.card" :size="entry.card.size" :completed="entry.card.completed" />

                            <race-preview v-else-if="entry.kind === 'race'" :standing="entry.standing" />

                            <!-- Only reachable if a BINGO event somehow has
                                 no bingoCard row yet — the controller creates
                                 one on first visit, so this is a safety net
                                 for the gap between "event created" and
                                 "anyone opened it," not an expected state. -->
                            <div v-else class="aspect-square rounded-lg ring ring-default bg-default p-4 flex flex-col items-center justify-center gap-1 text-center">
                                <u-icon name="i-lucide-image-off" class="size-8 text-dimmed" />
                                <span class="text-xs text-muted">{{ $t('events.preview_unavailable') }}</span>
                            </div>
                        </Link>
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
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import BingoPreview from '@/Components/BingoPreview.vue';
import BoardPreview from '@/Components/BoardPreview.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import RacePreview from '@/Components/RacePreview.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT, eventStatus, formatDate } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';
import { metricKindFor, rankedByLabel } from '@/Support/metrics';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const props = defineProps({
    // One shape per event, matching BoardController::mine():
    //   kind      - 'board' | 'race' | 'bingo' — server-computed from
    //               event type, and now what the preview slot branches on
    //               too, not just the icon and meta line.
    //   progress  - a board you are playing: { current, total, pct }
    //   preview   - a board you are playing: shape for BoardPreview
    //   card      - a bingo event's card: shape for BingoPreview
    //   standing  - a race you entered: { rank, gained, syncedAt, error, participants } | null
    // An event you only host but haven't played/entered still has a `kind`,
    // just none of the data blocks that come from actually participating —
    // the preview slot covers that gap itself now (see RacePreview's
    // "not entered" state, and BoardPreview's illustrative mode).
    boards: { type: Array, required: true },
    filter: { type: String, default: 'all' },
    counts: { type: Object, default: () => ({ all: 0, hosted: 0, playing: 0 }) },
});

const typeMeta = (board) => eventTypeMeta(board.type);

const breadcrumbs = [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events'), href: '/events' },
    { label: trans('boards.mine_title') },
];

// A drop race counts kills, a skill race XP — the noun comes from the type.
function rankedBy(board) {
    return rankedByLabel(board.metric, metricKindFor(board.type));
}

function sizeLabel(size) {
    return trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size] ?? size, tiles: BOARD_TILE_COUNT[size] ?? '?' });
}

// Same UTC-day comparison the cards use (Support/board.js), so a board never
// reads as Live on one page and Ended on another.
function statusLabel(board) {
    return trans(`boards.status_${eventStatus(board)}`);
}

const STATUS_DOT = { upcoming: 'bg-info', live: 'bg-success', paused: 'bg-warning', ended: 'bg-muted' };
const STATUS_TEXT = { upcoming: 'text-info', live: 'text-success', paused: 'text-warning', ended: 'text-muted' };

const statusOf = (board) => eventStatus(board);

function statusDot(board) {
    return STATUS_DOT[statusOf(board)] ?? 'bg-muted';
}

function statusText(board) {
    return STATUS_TEXT[statusOf(board)] ?? 'text-muted';
}

function dateRange(board) {
    if (!board.start_date && !board.end_date) return trans('boards.no_dates');

    return `${formatDate(board.start_date)} – ${formatDate(board.end_date)}`;
}

const filterTabs = computed(() => [
    { key: 'all', label: trans('events.mine_filter_all'), count: props.counts.all, href: '/my-events' },
    { key: 'hosted', label: trans('events.mine_filter_hosted'), count: props.counts.hosted, href: '/my-events?filter=hosted' },
    { key: 'playing', label: trans('events.mine_filter_playing'), count: props.counts.playing, href: '/my-events?filter=playing' },
]);

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);
</script>
