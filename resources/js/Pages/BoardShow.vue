<template>
    <Head :title="board.title">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <!-- u-page-header dropped entirely, not just its title slot:
                     it styles everything inside #title as a title, so the
                     description came out in the same uppercase display face
                     as the heading. A plain flex row instead, matching the
                     bingo and race pages so all three announce themselves the
                     same way. -->
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <event-type-heading :event="board" :can-edit="canEdit">
                            <template #meta>
                                <span class="inline-flex items-center gap-1.5">
                                    <u-icon name="i-lucide-grid-3x3" class="size-4 shrink-0" />
                                    {{ sizeLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <u-icon :name="board.mode === 'TEAM' ? 'i-lucide-users' : 'i-lucide-user'" class="size-4 shrink-0" />
                                    {{ board.mode === 'TEAM' ? $t('admin.board_mode_team') : $t('admin.board_mode_solo') }}
                                </span>
                            </template>
                    </event-type-heading>

                    <div class="flex gap-2 flex-wrap shrink-0">
                            <!-- Two buttons that both sound like "see who
                                 else is here", so they say what they
                                 actually do: this one draws everyone's
                                 marker ON the board, the one beside it opens
                                 the list of who is taking part. -->
                            <u-button
                                v-if="otherPlayers.length > 0"
                                :color="showOtherPlayers ? 'primary' : 'neutral'"
                                :variant="showOtherPlayers ? 'subtle' : 'outline'"
                                size="sm"
                                icon="i-lucide-map-pin"
                                :label="$t('board.show_players')"
                                :title="$t('board.show_players_desc')"
                                @click="showOtherPlayers = !showOtherPlayers"
                            />
                            <u-button
                                :href="`/events/${board.id}/participants`"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-users-round"
                                :label="$t('participants.open')"
                            />
                            <template v-if="canEdit">
                                <!-- Named for what they change — the tiles
                                     versus the event — rather than
                                     "edit mode" versus "edit board", which
                                     said nothing about which was which. -->
                                <u-button
                                    :color="editMode ? 'primary' : 'neutral'"
                                    :variant="editMode ? 'solid' : 'outline'"
                                    size="sm"
                                    :icon="editMode ? 'i-lucide-check' : 'i-lucide-grid-2x2-plus'"
                                    :label="editMode ? $t('bingo.editing_tiles') : $t('bingo.edit_tiles')"
                                    :title="$t('board.edit_tiles_desc')"
                                    @click="editMode = !editMode"
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
                            </template>
                            <u-button
                                :href="`/events/${board.id}/leaderboard`"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-trophy"
                                :label="$t('leaderboard.title')"
                            />
                    </div>
                </div>

                <u-card v-if="!hasTeam" class="mt-8">
                    <div class="flex flex-col items-center text-center gap-3 py-6">
                        <u-icon name="i-lucide-users" class="size-10 text-muted" />
                        <p class="font-semibold text-lg">{{ $t('board.no_team_title') }}</p>
                        <p class="text-sm text-muted max-w-md">{{ $t('board.no_team_desc') }}</p>
                        <u-button color="primary" :label="$t('board.go_to_teams')" href="/teams" />
                    </div>
                </u-card>

                <div v-else class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <div class="flex-1 w-full min-w-0 overflow-x-auto">
                        <!-- board-parchment/osrs-border ported as Tailwind utilities rather
                             than the old app's custom CSS classes (main.css) — same look,
                             but this codebase's convention is Tailwind-first custom CSS only
                             when Tailwind can't express it, and this can. -->
                        <div class="relative rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900" :class="minWidthClass">
                            <div :class="gridClass" class="grid gap-1">
                                <!-- Not gated on playerBoard existing — reaching this
                                     page at all already implies BoardAccess (see
                                     BoardController::show()'s access-gate redirect),
                                     and PlayerBoardController lazily creates the
                                     PlayerBoard row on first roll/toggle, same as the
                                     old getOrCreatePlayerBoard(). Gating the click on
                                     playerBoard already existing was a genuine
                                     dead-end bug: a brand-new player could never
                                     start, since nothing before this point ever
                                     creates that row. Caught by testing the actual
                                     cold-start flow through a real browser, not just
                                     curling a pre-seeded player's board. -->
                                <button
                                    v-for="tile in orderedTiles"
                                    :key="tile.position"
                                    type="button"
                                    class="aspect-square rounded-md relative cursor-pointer overflow-hidden hover:scale-105 transition-transform"
                                    :class="tileClasses(tile)"
                                    :title="tileTitle(tile) ?? trans('board.tile', { n: tile.position + 1 })"
                                    @click="handleTileClick(tile)"
                                >
                                    <!-- No z-index anywhere in here on purpose — see tileClasses()'s
                                         note. Paint order relies entirely on DOM order (later =
                                         on top), so nothing here can leak into the root stacking
                                         context and climb above a teleported modal. -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center px-1 overflow-hidden">
                                        <img
                                            v-if="tile.task?.icon_url"
                                            :src="tile.task.icon_url"
                                            :alt="tileTitle(tile)"
                                            class="max-h-[24%] max-w-[36%] object-contain shrink-0 mb-0.5"
                                            loading="lazy"
                                        />
                                        <u-icon v-else-if="isTileEmpty(tile)" name="i-lucide-plus" class="size-5 text-muted/50 shrink-0" />

                                        <p v-if="!isTileEmpty(tile)" class="w-full text-xs text-center leading-tight line-clamp-2 text-muted shrink-0">
                                            {{ tileTitle(tile) }}
                                        </p>
                                    </div>

                                    <span class="absolute top-1 left-1 text-xs font-bold text-muted leading-none">{{ tile.position + 1 }}</span>

                                    <span v-if="tile.type === 'SNAKE'" class="absolute top-1 right-1 text-error">
                                        <u-icon name="i-lucide-move-down" class="size-3" />
                                    </span>
                                    <span v-else-if="tile.type === 'LADDER'" class="absolute top-1 right-1 text-success">
                                        <u-icon name="i-lucide-move-up" class="size-3" />
                                    </span>

                                    <span v-if="isTileCompleted(tile)" class="absolute inset-0 flex items-center justify-center bg-success/20 rounded-md">
                                        <u-icon name="i-lucide-check-circle-2" class="size-5 text-success" />
                                    </span>

                                    <div v-if="playersOnTile(tile.position).length" class="absolute bottom-0.5 right-0.5 flex flex-wrap-reverse justify-end gap-0.5 max-w-[calc(100%-4px)]">
                                        <u-avatar
                                            v-for="p in playersOnTile(tile.position).slice(0, 3)"
                                            :key="p.id"
                                            :src="p.avatarUrl ?? undefined"
                                            :alt="p.name"
                                            size="3xs"
                                            class="ring-1 ring-default"
                                        />
                                        <span
                                            v-if="playersOnTile(tile.position).length > 3"
                                            class="text-[8px] leading-none bg-elevated rounded-full size-3.5 flex items-center justify-center ring-1 ring-default"
                                        >
                                            +{{ playersOnTile(tile.position).length - 3 }}
                                        </span>
                                    </div>
                                </button>
                            </div>

                            <!-- Percentage-based coordinates (viewBox 0 0 100 100),
                                 not pixel measurements like the old app's version —
                                 this grid is fluid-width (Tailwind grid-cols-N +
                                 aspect-square, no fixed tile size to measure), so a
                                 percentage overlay scales with it for free with no
                                 ResizeObserver. Approximates gap-1.5 as included in
                                 each cell's share rather than accounted separately;
                                 close enough at this gap size to not be visually off. -->
                            <svg
                                v-if="snakeLadderConnections.length"
                                class="board-svg-overlay"
                                viewBox="0 0 100 100"
                                preserveAspectRatio="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <defs>
                                    <marker id="arrow-snake" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
                                        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(239,68,68,0.55)" />
                                    </marker>
                                    <marker id="arrow-ladder" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
                                        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(34,197,94,0.55)" />
                                    </marker>
                                </defs>
                                <path
                                    v-for="(conn, i) in snakeLadderConnections"
                                    :key="i"
                                    :d="connectionPath(conn)"
                                    :stroke="conn.type === 'SNAKE' ? 'rgba(239,68,68,0.4)' : 'rgba(34,197,94,0.4)'"
                                    stroke-width="0.5"
                                    stroke-dasharray="2,1.2"
                                    :marker-end="conn.type === 'SNAKE' ? 'url(#arrow-snake)' : 'url(#arrow-ladder)'"
                                    fill="none"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">
                        <!-- Ported from Sidebar.vue: the dice roller only appears once the
                             CURRENT tile is marked complete — rolling isn't always available,
                             it's the reward for finishing what you're standing on. This isn't
                             enforced server-side either (old or new backend) — it's a UI pace,
                             not a hard rule — but the UI gate itself was missing entirely here. -->
                        <u-card v-if="playerBoard && currentTileCompleted">
                            <template #header>
                                <span class="font-semibold">{{ $t('board.roll_dice') }}</span>
                            </template>
                            <dice-roller
                                :rolling="rolling"
                                :last-roll="lastRoll"
                                :rolls-today="playerBoard?.dice_rolls_today ?? 0"
                                :roll-limit="board.dice_roll_limit"
                                @roll="roll"
                            />
                        </u-card>

                        <u-card v-if="!playerBoard">
                            <p class="text-sm text-muted">{{ $t('board.get_started_desc') }}</p>
                            <div class="mt-3">
                                <dice-roller :rolling="rolling" :last-roll="lastRoll" :rolls-today="0" :roll-limit="board.dice_roll_limit" @roll="roll" />
                            </div>
                        </u-card>

                        <u-card v-if="currentTile">
                            <template #header>
                                <span class="font-semibold">{{ $t('board.your_task') }}</span>
                            </template>
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="currentTile.task?.icon_url"
                                    :src="currentTile.task.icon_url"
                                    :alt="currentTileTitle"
                                    class="size-10 object-contain shrink-0"
                                />
                                <u-icon v-else name="i-lucide-scroll-text" class="size-10 text-muted shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm">{{ currentTileTitle }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ $t('board.tile', { n: currentTile.position + 1 }) }}</p>
                                    <p v-if="currentTile.task?.description" class="text-xs text-muted mt-1 leading-relaxed">
                                        {{ currentTile.task.description }}
                                    </p>
                                    <u-badge v-if="currentTile.type !== 'NORMAL'" :color="currentTile.type === 'SNAKE' ? 'error' : 'success'" size="sm" class="mt-1">
                                        {{ currentTile.type === 'SNAKE' ? '🐍' : '🪜' }} → {{ $t('board.tile', { n: (currentTile.target_position ?? 0) + 1 }) }}
                                    </u-badge>
                                </div>
                            </div>

                            <div class="mt-3">
                                <u-button
                                    v-if="!currentTileCompleted"
                                    color="success"
                                    variant="solid"
                                    size="sm"
                                    icon="i-lucide-check"
                                    block
                                    :label="$t('board.complete_tile')"
                                    @click="toggleTile(currentTile)"
                                />
                                <u-button
                                    v-else
                                    color="neutral"
                                    variant="outline"
                                    size="sm"
                                    icon="i-lucide-x"
                                    block
                                    :label="$t('board.uncomplete_tile')"
                                    @click="toggleTile(currentTile)"
                                />
                            </div>
                        </u-card>

                        <u-card v-if="clickedTile && clickedTile.position !== playerBoard?.current_position">
                            <template #header>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">{{ $t('board.tile_info') }}</span>
                                    <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-x" :aria-label="$t('common.close')" @click="clickedTile = null" />
                                </div>
                            </template>
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="clickedTile.task?.icon_url"
                                    :src="clickedTile.task.icon_url"
                                    :alt="clickedTileTitle"
                                    class="size-10 object-contain shrink-0"
                                />
                                <u-icon v-else name="i-lucide-scroll-text" class="size-10 text-muted shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm">{{ clickedTileTitle }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ $t('board.tile', { n: clickedTile.position + 1 }) }}</p>
                                    <p v-if="clickedTile.task?.description" class="text-xs text-muted mt-1 leading-relaxed">
                                        {{ clickedTile.task.description }}
                                    </p>
                                    <u-badge v-if="clickedTile.type !== 'NORMAL'" :color="clickedTile.type === 'SNAKE' ? 'error' : 'success'" size="sm" class="mt-1">
                                        {{ clickedTile.type === 'SNAKE' ? '🐍' : '🪜' }} → {{ $t('board.tile', { n: (clickedTile.target_position ?? 0) + 1 }) }}
                                    </u-badge>
                                </div>
                            </div>
                        </u-card>

                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('admin.editors') }}</span>
                            </template>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="author in board.authors" :key="author.id" class="flex items-center gap-1.5">
                                    <u-avatar :src="author.user.avatar_url ?? undefined" :alt="author.user.nickname || author.user.discord_username" size="xs" />
                                    <span class="text-xs">{{ author.user.nickname || author.user.discord_username }}</span>
                                </div>
                            </div>
                        </u-card>

                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('boards.meta') }}</span>
                            </template>
                            <div class="flex flex-wrap gap-2">
                                <u-badge color="neutral" variant="subtle" icon="i-lucide-calendar">
                                    {{ formatDate(board.start_date) }} – {{ formatDate(board.end_date) }}
                                </u-badge>
                                <u-badge color="neutral" variant="subtle" icon="i-lucide-grid-3x3">
                                    {{ formatBoardSize(board.size) }}
                                </u-badge>
                                <u-badge v-if="board.dice_roll_limit" color="neutral" variant="subtle" icon="i-lucide-dice-6">
                                    {{ $t('boards.roll_limit', { limit: board.dice_roll_limit }) }}
                                </u-badge>
                                <u-badge v-else color="neutral" variant="subtle" icon="i-lucide-dice-6">
                                    {{ $t('dice.unlimited') }}
                                </u-badge>
                                <u-badge :color="board.mode === 'TEAM' ? 'warning' : 'neutral'" variant="subtle" icon="i-lucide-users-round">
                                    {{ board.mode === 'TEAM' ? $t('board.mode_team') : $t('board.mode_solo') }}
                                </u-badge>
                            </div>
                        </u-card>

                        <u-card v-if="livePlayers.length">
                            <template #header>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">{{ $t('leaderboard.title') }}</span>
                                    <u-button :href="`/events/${board.id}/leaderboard`" variant="ghost" size="xs" color="neutral" trailing-icon="i-lucide-external-link" />
                                </div>
                            </template>
                            <div class="flex flex-col gap-1">
                                <div
                                    v-for="(p, i) in livePlayers.slice(0, 5)"
                                    :key="p.id"
                                    class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm"
                                    :class="isMyPlayerRow(p) ? 'bg-primary/10 ring-1 ring-primary/30' : ''"
                                >
                                    <span class="w-4 text-center text-xs font-bold shrink-0" :class="i < 3 ? 'text-primary' : 'text-muted'">{{ i + 1 }}</span>
                                    <img
                                        v-if="p.team?.icon_url"
                                        :src="p.team.icon_url"
                                        :alt="p.team.name"
                                        class="size-6 object-contain shrink-0"
                                        style="image-rendering: pixelated"
                                    />
                                    <span
                                        v-else-if="p.team"
                                        class="size-6 rounded shrink-0 bg-primary/20 flex items-center justify-center text-[8px] font-bold text-primary"
                                    >
                                        {{ p.team.name.slice(0, 2).toUpperCase() }}
                                    </span>
                                    <u-avatar v-else :src="p.user?.avatar_url ?? undefined" :alt="p.user?.nickname ?? p.user?.discord_username" size="xs" class="shrink-0" />
                                    <span class="flex-1 min-w-0 truncate font-medium">{{ p.team?.name ?? p.user?.nickname ?? p.user?.discord_username }}</span>
                                    <span class="text-xs text-muted shrink-0">#{{ p.current_position + 1 }}</span>
                                    <span
                                        class="text-xs font-semibold w-10 text-right shrink-0"
                                        :class="p.pathHasSnake ? 'text-error' : p.pathHasLadder ? 'text-success' : 'text-muted'"
                                    >
                                        {{ p.tilesRemaining }}🔲
                                    </span>
                                </div>
                            </div>
                            <div v-if="livePlayers.length > 5" class="mt-1 text-center">
                                <u-button
                                    :href="`/events/${board.id}/leaderboard`"
                                    variant="ghost"
                                    size="xs"
                                    color="neutral"
                                    :label="$t('leaderboard.show_all', { count: livePlayers.length })"
                                />
                            </div>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>

        <u-modal v-model:open="showCompleted" :title="$t('board.completed')">
            <template #body>
                <div class="text-center py-6">
                    <p class="text-6xl mb-4">🎉</p>
                    <p class="text-muted">{{ $t('board.completed_desc') }}</p>
                </div>
            </template>
            <template #footer>
                <u-button block color="primary" :label="$t('common.close')" @click="showCompleted = false" />
            </template>
        </u-modal>

        <client-only>
            <board-settings-modal v-model:open="showSettingsModal" :board="board" />
            <tile-list-editor
                v-model:open="showTileList"
                :event-id="board.id"
                type="SNAKES_LADDERS"
                :items="tiles"
                :total="tileCount"
            />
            <tile-edit-modal
                v-if="editingTile"
                :open="editingTile !== null"
                :event-id="board.id"
                :position="editingTile.position"
                :tile="editingTile.id ? editingTile : null"
                @update:open="(v) => !v && (editingTile = null)"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';
import DiceRoller from '@/Components/DiceRoller.vue';
import { BOARD_TILE_COUNT, BOARD_MIN_WIDTH, formatBoardSize, formatDate } from '@/Support/board';
import { useEventStream } from '@/Composables/useEventStream';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));
const TileListEditor = defineAsyncComponent(() => import('@/Components/TileListEditor.vue'));
const TileEditModal = defineAsyncComponent(() => import('@/Components/TileEditModal.vue'));

const props = defineProps({
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
    players: { type: Array, default: () => [] },
    hasTeam: { type: Boolean, default: true },
    canEdit: { type: Boolean, default: false },
});

// Everyone's positions, seeded from the render and then kept current by the
// board's own channel — a roll moves one player and everybody watching should
// see it, the same as a bingo square being ticked.
// The grid's own shape, for the heading's meta line. Via the shared helper
// rather than a second copy of the same lookup.
const sizeLabel = computed(() => formatBoardSize(props.board.size));

const livePlayers = ref([...props.players]);

useEventStream({
    url: () => `/events/${props.board.id}/stream`,
    event: 'players',
    onMessage: (payload) => (livePlayers.value = payload.players),
});


const showSettingsModal = ref(false);
const editingTile = ref(null);

// Opened automatically on ?setup=tiles — the redirect a freshly created
// event arrives on. onMounted because `location` does not exist during SSR.
const showTileList = ref(false);
onMounted(() => {
    if (props.canEdit && new URLSearchParams(window.location.search).get('setup') === 'tiles') {
        showTileList.value = true;
    }
});
const editMode = ref(false);
const rolling = ref(false);
const lastRoll = ref(null);
const showCompleted = ref(false);
const showOtherPlayers = ref(false);
// The tile the player last clicked (for inspection), separate from
// currentTile (where they actually are). Ported from the old Sidebar.vue's
// "Selected tile" panel — clicking a tile previews it, it does not toggle
// completion; only the current-position tile's own button in the sidebar
// can mark it complete, matching the old app (you complete tiles you've
// actually reached, not any tile you click).
const clickedTile = ref(null);

// Fed by PlayerBoardController::roll()'s 'last-roll' session flash (kept
// separate from the already-formatted 'board-save' toast text — see
// HandleInertiaRequests) — DiceRoller needs the raw number to pick a face.
const inertiaPage = usePage();
watch(
    () => inertiaPage.props?.flash?.lastRoll,
    (value) => {
        if (value !== null && value !== undefined) lastRoll.value = value;
    },
);

const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[props.board.size] ?? GRID_CLASSES.SIZE_7X7);
const minWidthClass = computed(() => BOARD_MIN_WIDTH[props.board.size] ?? BOARD_MIN_WIDTH.SIZE_7X7);

const cols = computed(() => ({ SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 }[props.board.size] ?? 7));
const tileCount = computed(() => BOARD_TILE_COUNT[props.board.size] ?? 49);

// Ported from the old Sidebar.vue's "your task" panel — shows the task
// (icon/title/description) for whichever real tile the player is currently
// standing on, plus the complete/uncomplete action. Distinct from clicking
// a tile directly in the grid: this is the always-visible "what do I do
// next" panel the old app had and this page was missing entirely.
const currentTile = computed(() => {
    if (!props.playerBoard) return null;
    return props.tiles.find((t) => t.position === props.playerBoard.current_position) ?? null;
});
const currentTileTitle = computed(() => currentTile.value?.title_override ?? currentTile.value?.task?.title ?? trans('tile_editor.no_task'));
const currentTileCompleted = computed(() => !!currentTile.value && (props.playerBoard?.completedTileIds.includes(currentTile.value.id) ?? false));

const clickedTileTitle = computed(() => clickedTile.value?.title_override ?? clickedTile.value?.task?.title ?? trans('tile_editor.no_task'));

// Ported from useBoardPage's otherPlayerStates/boardPlayerStates split — "me"
// is whichever row matches my user (SOLO) or my team (TEAM); everyone else
// only renders on the grid once the "show other players" toggle is on.
const authUser = computed(() => inertiaPage.props.auth?.user ?? null);
const isMyPlayerRow = (p) => (props.board.mode === 'TEAM' ? p.team_id === props.playerBoard?.team_id : p.user_id === authUser.value?.id);
const otherPlayers = computed(() => livePlayers.value.filter((p) => !isMyPlayerRow(p)));
// Reads the live list, so a roll by anyone moves their avatar on every
// open board rather than only after a refresh.
const visiblePlayers = computed(() => livePlayers.value.filter((p) => isMyPlayerRow(p) || showOtherPlayers.value));

function playersOnTile(position) {
    return visiblePlayers.value
        .filter((p) => p.current_position === position)
        .map((p) => ({
            id: p.team?.id ?? p.user_id,
            name: p.team?.name ?? p.user?.nickname ?? p.user?.discord_username ?? 'Player',
            avatarUrl: p.team?.icon_url ?? p.user?.avatar_url ?? null,
        }));
}

// Ported from the old GameBoard.vue's orderedTiles: a board doesn't get a
// full grid of Tile rows created on creation — only positions someone has
// actually configured exist. Missing positions render as NORMAL placeholder
// tiles (matching the old "empty-{position}" convention) so the grid is
// always complete regardless of how many tiles have been set up. Displayed
// in boustrophedon (snake) order — row 0 at the bottom-left, alternating
// direction per row — the actual Snakes & Ladders board numbering, not a
// plain reading order.
const orderedTiles = computed(() => {
    const n = cols.value;
    const tileMap = new Map(props.tiles.map((t) => [t.position, t]));
    const result = [];

    for (let row = n - 1; row >= 0; row--) {
        const leftToRight = row % 2 === 0;
        for (let col = 0; col < n; col++) {
            const adjustedCol = leftToRight ? col : n - 1 - col;
            const position = row * n + adjustedCol;
            if (position >= tileCount.value) continue;
            result.push(tileMap.get(position) ?? { id: null, position, type: 'NORMAL', target_position: null, task: null, title_override: null });
        }
    }

    return result;
});

// Ported from the old Board/SnakeLadder.vue, converted from pixel to
// percentage coordinates — see the template's comment on why.
const snakeLadderConnections = computed(() =>
    props.tiles
        .filter((t) => (t.type === 'SNAKE' || t.type === 'LADDER') && t.target_position !== null)
        .map((t) => ({ from: t.position, to: t.target_position, type: t.type })),
);

function tileCenterPercent(position) {
    const n = cols.value;
    const row = Math.floor(position / n);
    const col = position % n;
    const adjustedCol = row % 2 === 0 ? col : n - 1 - col;
    const visualRow = n - 1 - row;
    const cellSize = 100 / n;

    return { x: adjustedCol * cellSize + cellSize / 2, y: visualRow * cellSize + cellSize / 2 };
}

// Gentle quadratic bezier — same 0.18 curvature the old app used to keep
// lines subtle and clearly directed without cluttering the board.
function connectionPath(conn) {
    const start = tileCenterPercent(conn.from);
    const end = tileCenterPercent(conn.to);
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const cx = (start.x + end.x) / 2 - dy * 0.18;
    const cy = (start.y + end.y) / 2 + dx * 0.18;

    return `M ${start.x} ${start.y} Q ${cx} ${cy} ${end.x} ${end.y}`;
}

// Ported 1:1 from the old BoardTile.vue's tileClass computed: an unconfigured
// tile (no task, no title_override — includes the "not created yet"
// placeholder from orderedTiles) is "empty"; everything else gets a light
// primary tint. Outline-only (no background fill) for snake/ladder/current —
// .board-tile--snake/--ladder/--current in resources/css/app.css — plus
// --past for grayed-out already-passed tiles. This replaces an earlier
// version that improvised its own bg-tint palette instead of using the CSS
// classes that were already sitting there unused.
function isTileEmpty(tile) {
    return !tile.task && !tile.title_override;
}

function tileTitle(tile) {
    return tile.title_override ?? tile.task?.title ?? null;
}

function isTileCompleted(tile) {
    return props.playerBoard?.completedTileIds.includes(tile.id) ?? false;
}

function tileClasses(tile) {
    const current = props.playerBoard?.current_position;
    const classes = ['board-tile', isTileEmpty(tile) ? 'bg-muted/30' : 'bg-primary/5 dark:bg-primary/10'];

    if (tile.type === 'SNAKE') classes.push('board-tile--snake');
    if (tile.type === 'LADDER') classes.push('board-tile--ladder');

    if (current !== undefined && current !== null && tile.id !== null) {
        if (tile.position === current) classes.push('board-tile--current');
        else if (tile.position < current) classes.push('board-tile--past');
    }

    if (isTileCompleted(tile)) classes.push('board-tile--completed');

    return classes;
}

// Ported from useBoardPage's handleTileClick: in edit mode a click opens the
// tile editor (including on an unconfigured tile — that's how you configure
// one); otherwise it selects the tile for the sidebar's "Selected tile"
// preview. It does NOT toggle completion — see the clickedTile declaration
// above for why that's deliberate, not a missing feature.
function handleTileClick(tile) {
    if (editMode.value) {
        if (!props.canEdit) return;
        editingTile.value = tile;
        return;
    }
    clickedTile.value = tile;
}

function roll() {
    rolling.value = true;
    router.post(`/events/${props.board.id}/roll`, {}, { preserveScroll: true, onFinish: () => (rolling.value = false) });
}

// Ported from the old useBoardPage's onCompleteTile: completing the tile at
// the board's last position ends the run.
//
// Called "bingo" until 2026-08-20, which was a misnomer waiting to collide:
// this fires on finishing a Snakes & Ladders board, and BINGO is becoming
// a separate event type with its own line/full-board rules (ROADMAP phase 5).
function toggleTile(tile) {
    const wasCompleted = props.playerBoard?.completedTileIds.includes(tile.id) ?? false;
    const finishesBoard = !wasCompleted && tile.position === tileCount.value - 1;

    router.post(`/events/${props.board.id}/tiles/${tile.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (finishesBoard) showCompleted.value = true;
        },
    });
}
</script>
