<template>
    <Link :href="`/events/${board.id}`">
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

                    <div class="flex items-center gap-2 text-sm text-muted">
                        <u-icon name="i-lucide-calendar" class="size-4" />
                        <span>{{ formatDate(board.start_date) }} – {{ formatDate(board.end_date) }}</span>
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
                        <span>{{ access.label }}</span>
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
                <!-- A skill race isn't played; it's watched. -->
                <u-button
                    variant="ghost"
                    color="primary"
                    trailing-icon="i-lucide-arrow-right"
                    size="sm"
                    :label="board.size ? $t('boards.play') : $t('events.view_standings')"
                />
            </template>
        </u-page-card>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { formatBoardSize, formatDate, boardEventStatus, BOARD_ACCESS_META, BOARD_STATUS_STYLE } from '@/Support/board';
import { metricKindFor, rankedByLabel } from '@/Support/metrics';

const props = defineProps({
    board: { type: Object, required: true },
    // { current, total, pct } — omitted on the public index.
    progress: { type: Object, default: null },
});

const rankedBy = computed(() => rankedByLabel(props.board.metric, metricKindFor(props.board.type)));

const status = computed(() => BOARD_STATUS_STYLE[boardEventStatus(props.board.start_date, props.board.end_date)]);
const access = computed(() => (props.board.access_mode ? BOARD_ACCESS_META[props.board.access_mode] : undefined));
</script>
