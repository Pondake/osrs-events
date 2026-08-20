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
});

const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[props.size] ?? GRID_CLASSES.SIZE_7X7);
const cols = computed(() => ({ SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 }[props.size] ?? 7));

/**
 * Illustrative only — a real board's snakes and ladders are placed by
 * whoever builds it, not generated. These are derived from the position so
 * the preview stays stable as the user flips between sizes (a random
 * placement would reshuffle on every keystroke and read as noise), and
 * spaced by a fraction of the tile count so every size gets a few.
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

            const isSnake = position > 0 && position % Math.max(7, Math.floor(total / 5)) === 3;
            const isLadder = position > 0 && position % Math.max(5, Math.floor(total / 6)) === 1 && !isSnake;

            result.push({
                position,
                type: isSnake ? 'SNAKE' : isLadder ? 'LADDER' : 'NORMAL',
                isPlayer: position === 0,
            });
        }
    }

    return result;
});

function tileClasses(tile) {
    const classes = ['board-tile'];

    if (tile.type === 'SNAKE') classes.push('board-tile--snake');
    else if (tile.type === 'LADDER') classes.push('board-tile--ladder');
    else if (tile.isPlayer) classes.push('board-tile--current');

    classes.push(tile.type === 'NORMAL' && !tile.isPlayer ? 'bg-muted/30' : 'bg-primary/5 dark:bg-primary/10');

    return classes;
}
</script>
