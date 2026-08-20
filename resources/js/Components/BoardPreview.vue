<template>
    <div class="rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900">
        <div class="grid gap-1" :class="gridClass">
            <div
                v-for="tile in tiles"
                :key="tile.position"
                class="aspect-square rounded-md relative transition-all duration-300"
                :class="tileClasses(tile)"
            >
                <span class="absolute top-0.5 left-1 text-[8px] font-bold text-muted leading-none">{{ tile.position + 1 }}</span>
                <span v-if="tile.type === 'SNAKE'" class="absolute inset-0 flex items-center justify-center text-error text-xs">🐍</span>
                <span v-else-if="tile.type === 'LADDER'" class="absolute inset-0 flex items-center justify-center text-success text-xs">🪜</span>
                <span v-else-if="tile.isPlayer" class="absolute inset-0 flex items-center justify-center">
                    <u-icon name="i-lucide-user" class="size-3 text-primary" />
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { BOARD_TILE_COUNT } from '@/Support/board';

const props = defineProps({
    size: { type: String, default: 'SIZE_7X7' },
    mode: { type: String, default: 'SOLO' },
    /**
     * Real special tiles as [{ position, type }]. Given, the preview shows an
     * actual board; omitted, it invents an illustrative one — which is what
     * the create-event form needs, since there is no board yet at that point.
     */
    specialTiles: { type: Array, default: null },
    /** The viewer's position on it, or null when there is no player. */
    currentPosition: { type: Number, default: null },
    completedPositions: { type: Array, default: () => [] },
});

const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[props.size] ?? GRID_CLASSES.SIZE_7X7);

// Maps rather than repeated .find() calls: a 9×9 board is 81 cells, and the
// preview redraws on every parent update.
const realTypes = computed(() => new Map((props.specialTiles ?? []).map((t) => [t.position, t.type])));
const completed = computed(() => new Set(props.completedPositions ?? []));
const cols = computed(() => ({ SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 }[props.size] ?? 7));

/**
 * Illustrative placement, used only when `specialTiles` is absent. A real
 * board's snakes and ladders are placed by whoever builds it, not generated;
 * these are derived from the position so the preview stays stable as the user
 * flips between sizes (a random placement would reshuffle on every keystroke
 * and read as noise), and spaced by a fraction of the tile count so every
 * size gets a few.
 */
const tiles = computed(() => {
    const total = BOARD_TILE_COUNT[props.size] ?? 49;
    const n = cols.value;
    const result = [];

    // Same boustrophedon layout the real board uses (BoardShow.vue) — row 0
    // bottom-left, alternating direction, so the preview isn't lying about
    // how the numbering runs.
    for (let row = n - 1; row >= 0; row--) {
        const leftToRight = row % 2 === 0;
        for (let col = 0; col < n; col++) {
            const adjustedCol = leftToRight ? col : n - 1 - col;
            const position = row * n + adjustedCol;
            if (position >= total) continue;

            let type;
            if (props.specialTiles) {
                type = realTypes.value.get(position) ?? 'NORMAL';
            } else {
                const isSnake = position > 0 && position % Math.max(7, Math.floor(total / 5)) === 3;
                const isLadder = position > 0 && position % Math.max(5, Math.floor(total / 6)) === 1 && !isSnake;
                type = isSnake ? 'SNAKE' : isLadder ? 'LADDER' : 'NORMAL';
            }

            result.push({
                position,
                type,
                isPlayer: props.currentPosition === null ? position === 0 : position === props.currentPosition,
                isDone: completed.value.has(position),
            });
        }
    }

    return result;
});

// Reuses the real board's tile styling (app.css) so the preview reads as
// the same object at a smaller size rather than a separate diagram.
function tileClasses(tile) {
    const classes = ['board-tile'];

    if (tile.type === 'SNAKE') classes.push('board-tile--snake');
    else if (tile.type === 'LADDER') classes.push('board-tile--ladder');

    // Current position wins over "already passed": both are true of the tile
    // you're standing on, and where you ARE is the more useful of the two.
    if (tile.isPlayer) classes.push('board-tile--current');
    else if (tile.isDone) classes.push('board-tile--past');

    classes.push(tile.type === 'NORMAL' && !tile.isPlayer ? 'bg-muted/30' : 'bg-primary/5 dark:bg-primary/10');

    return classes;
}
</script>
