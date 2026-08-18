<template>
    <Head :title="board.title">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-page-header :title="board.title" :description="board.description ?? ''">
                    <template #headline>
                        <u-badge :label="board.mode" color="neutral" variant="subtle" />
                    </template>
                    <template #links>
                        <div v-if="canEdit" class="flex gap-2">
                            <u-button
                                :color="editMode ? 'primary' : 'neutral'"
                                :variant="editMode ? 'solid' : 'outline'"
                                size="sm"
                                :icon="editMode ? 'i-lucide-eye' : 'i-lucide-pencil'"
                                :label="editMode ? 'View mode' : 'Edit tiles'"
                                @click="editMode = !editMode"
                            />
                            <u-button
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-settings"
                                label="Edit board"
                                @click="showSettingsModal = true"
                            />
                            <u-button
                                :href="`/boards/${board.id}/leaderboard`"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-trophy"
                                label="Leaderboard"
                            />
                        </div>
                        <u-button
                            v-else
                            :href="`/boards/${board.id}/leaderboard`"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-trophy"
                            label="Leaderboard"
                        />
                    </template>
                </u-page-header>

                <div class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <div class="flex-1 w-full min-w-0 overflow-x-auto">
                        <div :class="[gridClass, minWidthClass]" class="grid gap-1.5">
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
                                class="aspect-square rounded-md border flex items-center justify-center text-xs font-semibold transition-colors cursor-pointer hover:border-primary"
                                :class="tileClasses(tile)"
                                :title="tile.title_override ?? tile.task?.title"
                                @click="handleTileClick(tile)"
                            >
                                {{ tile.position + 1 }}
                            </button>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">
                        <u-card>
                            <template #header>
                                <span class="font-semibold">Your progress</span>
                            </template>
                            <dl v-if="playerBoard" class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-muted">Current tile</dt>
                                    <dd>{{ playerBoard.current_position + 1 }} / {{ tiles.length }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-muted">Tiles completed</dt>
                                    <dd>{{ playerBoard.completedTileIds.length }}</dd>
                                </div>
                                <div v-if="board.dice_roll_limit" class="flex justify-between">
                                    <dt class="text-muted">Rolls today</dt>
                                    <dd>{{ playerBoard.dice_rolls_today }} / {{ board.dice_roll_limit }}</dd>
                                </div>
                            </dl>
                            <p v-else class="text-sm text-muted">
                                Roll the dice or complete a tile to get started — your first roll starts your progress.
                            </p>
                            <template #footer>
                                <u-button
                                    color="primary"
                                    block
                                    icon="i-lucide-dice-6"
                                    label="Roll dice"
                                    :loading="rolling"
                                    @click="roll"
                                />
                            </template>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal v-model:open="showSettingsModal" :board="board" />
            <tile-edit-modal
                v-if="editingTile"
                :open="editingTile !== null"
                :board-id="board.id"
                :position="editingTile.position"
                :tile="editingTile.id ? editingTile : null"
                @update:open="(v) => !v && (editingTile = null)"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';
import { BOARD_TILE_COUNT, BOARD_MIN_WIDTH } from '@/Support/board';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));
const TileEditModal = defineAsyncComponent(() => import('@/Components/TileEditModal.vue'));

const props = defineProps({
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
});

const showSettingsModal = ref(false);
const editingTile = ref(null);
const editMode = ref(false);
const rolling = ref(false);

const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[props.board.size] ?? GRID_CLASSES.SIZE_7X7);
const minWidthClass = computed(() => BOARD_MIN_WIDTH[props.board.size] ?? BOARD_MIN_WIDTH.SIZE_7X7);

const cols = computed(() => ({ SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 }[props.board.size] ?? 7));
const tileCount = computed(() => BOARD_TILE_COUNT[props.board.size] ?? 49);

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

function tileClasses(tile) {
    if (props.playerBoard?.completedTileIds.includes(tile.id)) {
        return 'bg-primary/20 border-primary text-primary';
    }
    if (tile.type === 'SNAKE') return 'bg-error/10 border-error/30';
    if (tile.type === 'LADDER') return 'bg-success/10 border-success/30';
    if (tile.id === null) return 'bg-elevated/50 border-dashed border-default text-muted';
    return 'bg-elevated border-default';
}

function handleTileClick(tile) {
    if (editMode.value) {
        editingTile.value = tile;
        return;
    }
    if (tile.id === null) return; // nothing to toggle on an unconfigured tile in play mode
    toggleTile(tile);
}

function roll() {
    rolling.value = true;
    router.post(`/boards/${props.board.id}/roll`, {}, { preserveScroll: true, onFinish: () => (rolling.value = false) });
}

function toggleTile(tile) {
    router.post(`/boards/${props.board.id}/tiles/${tile.id}/toggle`, {}, { preserveScroll: true });
}
</script>
