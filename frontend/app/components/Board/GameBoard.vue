<template>
  <div class="relative board-parchment rounded-xl p-3 osrs-border">
    <!-- Grid -->
    <div ref="gridRef" :class="`board-grid board-grid-${cols}`">
      <board-tile
        v-for="tile in orderedTiles"
        :key="tile.position"
        :position="tile.position"
        :display-number="tile.displayNumber"
        :title="tile.displayTitle ?? tile.titleOverride ?? tile.task?.title ?? null"
        :icon-url="tile.task?.iconUrl ?? null"
        :type="tile.type"
        :target-position="tile.targetPosition ?? null"
        :completed="completedPositions.has(tile.position)"
        :is-current="currentPosition === tile.position"
        :is-past="currentPosition > 0 && tile.position < currentPosition"
        :players="playersOnTile(tile.position)"
        :edit-mode="editMode"
        :is-empty="!tile.task && !tile.titleOverride"
        @click="handleTileClick"
      />
    </div>

    <!-- SVG overlay for snake/ladder lines -->
    <board-snake-ladder
      v-if="tileSize > 0 && connections.length > 0"
      :connections="connections"
      :board-size="cols"
      :tile-size="tileSize"
      :gap="4"
    />
  </div>
</template>

<script setup lang="ts">
import type { TileEntity, PlayerBoardEntity, BoardSize } from '~/types/graphql'

const props = withDefaults(defineProps<{
  tiles: TileEntity[];
  boardSize: BoardSize;
  currentPosition?: number;
  completedTilePositions?: number[];
  playerStates?: PlayerBoardEntity[];
  editMode?: boolean;
}>(), {
  currentPosition: -1,
  completedTilePositions: () => [],
  playerStates: () => [],
  editMode: false,
});

const emit = defineEmits<{ tileClick: [position: number] }>();

const cols = computed(() => {
  const map = { SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 };
  return map[props.boardSize] ?? 7;
});

/**
 * Build display order: row 0 at bottom (position 0), snaking columns.
 * Returns tiles sorted for CSS grid display (top-left first).
 */
const orderedTiles = computed(() => {
  const n = cols.value;
  const tileMap = new Map(props.tiles.map(t => [t.position, t]));
  const result = [];

  // Iterate rows from top (visually) to bottom
  for (let row = n - 1; row >= 0; row--) {
    // Even rows (from bottom): left→right. Odd rows: right→left.
    const leftToRight = row % 2 === 0;
    for (let col = 0; col < n; col++) {
      const adjustedCol = leftToRight ? col : n - 1 - col;
      const position = row * n + adjustedCol;

      const tile = tileMap.get(position) ?? {
        id: `empty-${position}`,
        position,
        type: 'NORMAL' as TileEntity['type'],
        targetPosition: null,
        titleOverride: null,
        task: null,
      } as TileEntity;

      // Display number: 1-based, position 0 = tile 1
      const displayNumber = position + 1;
      result.push({ ...tile, displayNumber });
    }
  }
  return result;
});

const completedPositions = computed(() => new Set(props.completedTilePositions));

const connections = computed(() =>
  props.tiles
    .filter(t => (t.type === 'SNAKE' || t.type === 'LADDER') && t.targetPosition !== null)
    .map(t => ({ from: t.position, to: t.targetPosition as number, type: t.type as 'SNAKE' | 'LADDER' })),
);

function playersOnTile(position: number) {
  return props.playerStates
    .filter(p => p.currentPosition === position)
    .map(p => ({
      id: (p as any).teamId ?? p.userId,
      discordUsername: (p as any).team?.name ?? p.user?.discordUsername ?? 'Player',
      avatarUrl: (p as any).team?.iconUrl ?? p.user?.avatarUrl ?? null,
      isTeam: !!(p as any).team,
    }))
}

function handleTileClick(position: number) {
  emit('tileClick', position);
}

// Measure tile size for SVG overlay
const gridRef = ref<HTMLElement | null>(null);
const tileSize = ref(0);

function measureTileSize() {
  const firstTile = gridRef.value?.querySelector('.board-tile') as HTMLElement | null;
  if (firstTile) {
    tileSize.value = firstTile.offsetWidth;
  }
}

onMounted(() => {
  measureTileSize();
});

useResizeObserver(gridRef, () => {
  measureTileSize();
});
</script>
