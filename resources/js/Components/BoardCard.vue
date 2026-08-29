<template>
    <Link :href="`/events/${board.id}`">
        <!-- `min-w-0` on every one of the card's own boxes, not just on the
             row inside it. Each of PageCard's nested flex containers defaults
             to `min-width: auto`, so an unshrinkable child — the title, which
             is `truncate`, therefore `white-space: nowrap` — pushes the whole
             stack wider than its column. Setting it on the innermost element
             alone does nothing while its parents can still grow: on a 768px
             tablet one 74-character title dragged the page 390px sideways. -->
        <u-page-card
            class="h-full min-w-0 relative z-0 overflow-hidden hover:border-primary transition-colors cursor-pointer"
            :ui="{ container: 'min-w-0', wrapper: 'min-w-0', body: 'w-full min-w-0', title: 'min-w-0' }"
        >
            <!-- Which kind of event this is, branded into the corner rather
                 than said in a badge — a pill repeated across a whole grid of
                 cards read as the loudest thing on the page for the fact you
                 need least often, since the description rows below already
                 imply it (a grid size, a roll limit, a metric to race on).
                 `-bottom-3 -right-3` lets it bleed past the card's own edge
                 rather than sit pinned inside it, which is what keeps it
                 reading as a mark burned into the corner instead of a fourth
                 icon in the list above. `overflow-hidden` on the card clips
                 it to the rounded corner. Still named for anyone not reading
                 it visually — `sr-only`, not `aria-hidden`, since removing
                 the badge means this is now the only place the type is said
                 at all.

                 The card needs `z-0`, not just `relative`, for `-z-10` below
                 to mean anything. `position: relative` alone does NOT open a
                 new stacking context — only a positioned element with an
                 explicit z-index does — so without it, "-z-10" was scoped to
                 the nearest ancestor that DOES open one, however far up the
                 tree that is, and the icon could end up buried behind
                 whatever that unrelated context painted on top. `z-0` makes
                 this card own its own stacking context, so `-z-10` on the
                 icon is only ever "behind this card's own content" and
                 nothing further away. -->
            <!-- Two things wrong with `text-primary/20`, not one.

                 First, colour: `primary` is the one hue this page already
                 uses for something you're meant to act on — the "Play"
                 button, the link, the focus ring. Painting it into a corner
                 as decoration makes it compete with itself; a watermark
                 reads as background texture precisely by NOT being the
                 colour that means "click here" elsewhere on the same card.
                 `text-muted` (the same grey the meta rows already use) is
                 background by definition.

                 Second, and the one that actually looked broken: `/20` sets
                 alpha on the colour, which is per-PATH alpha. `worm`'s
                 stroke crosses itself, and two 20%-alpha strokes overlapping
                 don't stay 20% where they cross — they compound to ~36%,
                 which is exactly the darker double-line the icon showed at
                 its self-intersections. `opacity-20` on the svg itself
                 forces the icon to render at full, solid colour internally
                 first and composites THAT flattened result against the
                 background afterward — self-overlaps disappear because
                 there's nothing left to double once it's one flat layer. -->
            <u-icon
                v-if="typeMeta"
                :name="typeMeta.icon"
                class="absolute -bottom-3 -right-3 -z-10 size-24 text-muted opacity-20 pointer-events-none"
            />
            <span v-if="typeMeta" class="sr-only">{{ typeMeta.label }}</span>

            <template #title>
                <!-- `min-w-0` on both, and it is not decoration. `truncate`
                     sets `white-space: nowrap`, and a flex item defaults to
                     `min-width: auto` — so a long title stops being able to
                     shrink and pushes the card past its column instead of
                     ellipsing. On a 768px tablet, where the hub is two
                     columns, one 74-character event title dragged the whole
                     page 390px wider than the screen. -->
                <div class="flex items-center justify-between gap-3 w-full min-w-0">
                    <span class="truncate min-w-0">{{ board.title }}</span>
                    <div class="flex items-center gap-1.5 text-xs font-medium rounded-md px-2 py-1 shrink-0" :class="status.class">
                        <u-icon :name="status.icon" class="size-3.5" />
                        <span>{{ $t(status.labelKey) }}</span>
                    </div>
                </div>
            </template>

            <template #description>
                <div class="flex flex-col gap-2 mt-2">
                    <!-- Progress only renders where a PlayerBoard was passed
                         in (the "my boards" list) — the public board index
                         has no per-user state to show. -->
                    <div v-if="progress" class="mb-1">
                        <div class="flex justify-between text-xs text-muted mb-1">
                            <span>{{ $t('boards.your_progress', { current: progress.current, total: progress.total }) }}</span>
                            <span>{{ progress.pct }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" :style="{ width: `${progress.pct}%` }" />
                        </div>
                    </div>

                    <!-- Both dates are nullable — an open-ended board has
                         neither, and this rendered formatDate()'s em-dash
                         placeholder twice ("— – —") as if the data were
                         missing rather than absent by design. -->
                    <div v-if="dateRange" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-calendar" class="size-4" />
                        <span>{{ dateRange }}</span>
                    </div>

                    <!-- Grid size and dice limit live on the BOARD, and not
                         every event type has one. Rendered unconditionally
                         this crashed the whole hub the moment a boardless
                         event appeared in it: size was null, and $t calls
                         toString() on whatever it is given to substitute. -->
                    <div v-if="board.size" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-grid-3x3" class="size-4" />
                        <span>{{ $t('boards.size', { size: formatBoardSize(board.size) }) }}</span>
                    </div>

                    <!-- What a metric event races on, in the slot the grid
                         size would otherwise occupy. -->
                    <div v-else-if="board.metric" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-trophy" class="size-4" />
                        <span>{{ rankedBy }}</span>
                    </div>

                    <!-- Bingo has a grid too, just not the same one. -->
                    <div v-else-if="board.bingo_size" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-grid-3x3" class="size-4" />
                        <span>{{ $t('boards.bingo_card', { size: board.bingo_size }) }}</span>
                    </div>

                    <div v-if="board.dice_roll_limit" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-dice-6" class="size-4" />
                        <span>{{ $t('boards.roll_limit', { limit: board.dice_roll_limit }) }}</span>
                    </div>

                    <div v-if="access" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon :name="access.icon" class="size-4" />
                        <span>{{ $t(access.labelKey) }}</span>
                    </div>

                    <div v-if="board.mode === 'TEAM'" class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-users" class="size-4" />
                        <span>{{ $t('boards.team_mode') }}</span>
                    </div>

                    <div v-if="board.authors?.length" class="flex items-center gap-2 mt-1">
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
                <!-- One label regardless of kind, same as Boards/Mine.vue's
                     equivalent button — this used to read `board.size` to
                     pick "Play" vs "View standings", which is really just
                     "is this a Snakes & Ladders board", so bingo fell into
                     "View standings" the same way it did there. Every kind
                     goes to the same href either way. -->
                <u-button
                    variant="ghost"
                    color="primary"
                    trailing-icon="i-lucide-arrow-right"
                    size="sm"
                    :label="$t('common.open')"
                />
            </template>
        </u-page-card>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { formatBoardSize, formatDate, eventStatus, BOARD_ACCESS_META, BOARD_STATUS_STYLE } from '@/Support/board';
import { metricKindFor, rankedByLabel } from '@/Support/metrics';
import { eventTypeMeta } from '@/Support/eventTypes';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    board: { type: Object, required: true },
    // { current, total, pct } — omitted on the public index.
    progress: { type: Object, default: null },
});

const typeMeta = computed(() => eventTypeMeta(props.board.type));

/**
 * Null when the board is open-ended, so the row is dropped rather than filled
 * with placeholders. Half-open ranges read as a bound, not as a gap.
 */
const dateRange = computed(() => {
    const { start_date: start, end_date: end } = props.board;

    if (start && end) return `${formatDate(start)} – ${formatDate(end)}`;
    if (start) return trans('boards.date_from', { date: formatDate(start) });
    if (end) return trans('boards.date_until', { date: formatDate(end) });

    return null;
});

const rankedBy = computed(() => rankedByLabel(props.board.metric, metricKindFor(props.board.type)));

const status = computed(() => BOARD_STATUS_STYLE[eventStatus(props.board)]);
const access = computed(() => (props.board.access_mode ? BOARD_ACCESS_META[props.board.access_mode] : undefined));
</script>
