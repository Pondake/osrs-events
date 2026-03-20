<template>
  <svg
    class="board-svg-overlay"
    :viewBox="`0 0 ${svgSize} ${svgSize}`"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <defs>
      <!-- Small minimal arrowhead for snakes (red) -->
      <marker id="arrow-snake" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(239,68,68,0.55)" />
      </marker>
      <!-- Small minimal arrowhead for ladders (amber) -->
      <marker id="arrow-ladder" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(245,158,11,0.55)" />
      </marker>
    </defs>

    <g v-for="(conn, i) in connections" :key="i">
      <path
        :d="pathFor(conn)"
        :stroke="conn.type === 'SNAKE' ? 'rgba(239,68,68,0.4)' : 'rgba(245,158,11,0.4)'"
        :stroke-width="strokeWidth"
        stroke-dasharray="5,3"
        :marker-end="conn.type === 'SNAKE' ? 'url(#arrow-snake)' : 'url(#arrow-ladder)'"
        fill="none"
        stroke-linecap="round"
      />
    </g>
  </svg>
</template>

<script setup lang="ts">
interface Connection {
  from: number;
  to: number;
  type: 'SNAKE' | 'LADDER';
}

interface Props {
  connections: Connection[];
  boardSize: number;
  tileSize: number;
  gap?: number;
}

const props = withDefaults(defineProps<Props>(), { gap: 4 });

// Total SVG size = board visual size (tiles * size + gaps)
const svgSize = computed(
  () => props.boardSize * props.tileSize + (props.boardSize - 1) * props.gap,
);

// Thin line — max 1.5px regardless of tile size
const strokeWidth = computed(() => Math.min(1.5, Math.max(1, props.tileSize * 0.04)));

/**
 * Convert a 0-based tile position to SVG center coordinates.
 * Mirrors the snaking grid logic: row 0 is at the bottom, even rows L→R, odd rows R→L.
 */
function tileCenter(position: number): { x: number; y: number } {
  const n = props.boardSize;
  const row = Math.floor(position / n);
  const col = position % n;

  // Adjust column for snaking direction
  const adjustedCol = row % 2 === 0 ? col : n - 1 - col;

  // Visual row from top: row n-1 (highest position row) is at top
  const visualRow = n - 1 - row;

  const cellSize = props.tileSize + props.gap;
  const x = adjustedCol * cellSize + props.tileSize / 2;
  const y = visualRow * cellSize + props.tileSize / 2;

  return { x, y };
}

/**
 * Build a gentle quadratic bezier from tile `from` to tile `to`.
 * Reduced curvature so lines are subtle and don't clutter the board.
 */
function pathFor(conn: Connection): string {
  const start = tileCenter(conn.from);
  const end = tileCenter(conn.to);

  const dx = end.x - start.x;
  const dy = end.y - start.y;
  // Subtle curve — 0.18 offset keeps it clearly directed without being messy
  const cx = (start.x + end.x) / 2 - dy * 0.18;
  const cy = (start.y + end.y) / 2 + dx * 0.18;

  return `M ${start.x} ${start.y} Q ${cx} ${cy} ${end.x} ${end.y}`;
}
</script>
