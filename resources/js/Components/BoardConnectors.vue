<template>
    <svg
        v-if="connections.length"
        class="board-svg-overlay"
        viewBox="0 0 100 100"
        preserveAspectRatio="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <defs v-if="clipEnds">
            <!-- Real <rect> elements, not v-html: setting innerHTML on a
                 <clipPath> leaves it empty, and an empty clip hides everything
                 it is applied to. The board showed no grip at all until this
                 was found — the shapes were being drawn and then clipped into
                 nothing. -->
            <clipPath v-for="conn in connections" :id="`sl-ends-${uid}-${conn.key}`" :key="conn.key">
                <rect
                    v-for="(r, i) in conn.ends"
                    :key="i"
                    :x="r.x"
                    :y="r.y"
                    :width="r.size"
                    :height="r.size"
                />
            </clipPath>
        </defs>

        <g
            v-for="conn in connections"
            :key="conn.key"
            :clip-path="clipEnds ? `url(#sl-ends-${uid}-${conn.key})` : undefined"
            :class="behind(conn) ? 'sl-behind' : null"
            shape-rendering="crispEdges"
        >
            <template v-if="conn.type === 'SNAKE'">
                <g :fill="palette.body" v-html="conn.parts.trunk" />
                <g :fill="palette.belly" v-html="conn.parts.belly" />
                <g :fill="palette.eye" v-html="conn.parts.eyes" />
            </template>
            <g v-else :transform="conn.parts.transform">
                <g :fill="palette.wood" v-html="conn.parts.base" />
                <g :fill="palette.woodLight" v-html="conn.parts.edge" />
            </g>
        </g>

        <!-- The one being pointed at, drawn whole and unclipped so it comes
             forward over the tiles it crosses. Its own element rather than a
             branch in the loop above, so it can fade in and out — appearing
             and vanishing on the same frame as the cursor reads as a glitch. -->
        <transition name="sl-flare">
            <g v-if="clipEnds && active" :key="active.key" shape-rendering="crispEdges">
                <template v-if="active.type === 'SNAKE'">
                    <g :fill="palette.body" v-html="active.parts.trunk" />
                    <g :fill="palette.belly" v-html="active.parts.belly" />
                    <g :fill="palette.eye" v-html="active.parts.eyes" />
                </template>
                <g v-else :transform="active.parts.transform">
                    <g :fill="palette.wood" v-html="active.parts.base" />
                    <g :fill="palette.woodLight" v-html="active.parts.edge" />
                </g>
            </g>
        </transition>
    </svg>
</template>

<script setup>
import { computed, useId } from 'vue';

import { PALETTE } from '@/Support/snakesLadders';

/**
 * One layer of the snake/ladder overlay on a Snakes & Ladders board.
 *
 * BoardShow renders it twice with the grid between. The plain pass runs under
 * every tile, so a connector is a hint behind the tasks it crosses and never
 * covers one. The `clip-ends` pass draws the same shapes again on top, clipped
 * to each connector's own two tiles, so its two ends come back out and grip
 * them.
 *
 * The shapes arrive as ready-made SVG strings from Support/snakesLadders.js.
 * They are built there rather than as elements here because a 9×9 board with
 * eight connectors is well over a thousand rects, and a v-for per rect costs
 * a component-sized amount of bookkeeping for markup that never changes on
 * its own — it is recomputed wholesale whenever a tile moves.
 */
const props = defineProps({
    connections: { type: Array, required: true },
    // The top pass. Off by default: the layer underneath is the one that has
    // to exist for the board to look right at all.
    clipEnds: { type: Boolean, default: false },
    // The connector the reader is pointing at, if any.
    activeKey: { type: String, default: null },
    // Where the reader's own piece is standing, so the board can say which
    // connectors are behind them.
    passedPosition: { type: Number, default: null },
});

/**
 * The connector being pointed at, drawn a second time without a clip.
 *
 * It covers the tasks it crosses — deliberately, and only while someone is
 * pointing at it. Permanent ink can be strong enough to answer "where does
 * this go?" or it can spare the labels, never both; that was measured three
 * different ways. Ink on demand can do both, because nobody is reading those
 * labels at the moment they ask the question.
 */
const active = computed(() => props.connections.find((conn) => conn.key === props.activeKey) ?? null);

/**
 * Whether the reader has already gone past where this connector starts.
 *
 * Faded rather than hidden, and "behind you" rather than "unusable": a snake
 * further up can still drop you below one of these, and then it is live again.
 * The fade says which parts of the board are not on your way right now, the
 * same thing the greyed-out tiles behind you say.
 */
function behind(conn) {
    return props.passedPosition !== null && conn.from < props.passedPosition;
}

const palette = PALETTE;

// Both passes render the same connectors, so their clipPath ids would collide
// — and a duplicate id resolves to whichever the document saw first, which
// would silently clip the bottom pass to the end tiles too.
const uid = useId();
</script>
