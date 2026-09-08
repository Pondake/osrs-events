<template>
    <!--
        The colour lives on this element as Tailwind variants, not in the
        scoped block below: a `:global(.dark)` selector there is thrown away
        by the SFC compiler, which leaves dark mode painting the light-mode
        ink. The whole field inherits one colour through `currentColor`.
    -->
    <div
        class="app-bg text-stone-900 dark:text-stone-300"
        aria-hidden="true"
        :style="{
            '--bg-tile-w': `${shape.tileW}px`,
            '--bg-tile-h': `${shape.tileH}px`,
            '--bg-accent-peak': hero ? 0.3 : 0.15,
        }"
    >
        <svg class="app-bg__field" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- The repeating unit. Every motif draws it around the same
                     origin, so a placement below is only a lattice coordinate.

                     The opacities differ per motif on purpose: they are set so
                     each tile carries about the same amount of ink per square
                     pixel (~0.10 of solid), because a diamond outline and a
                     filled square at the same opacity do not read as the same
                     weight. Drawn to taste they came out at 0.11 / 0.07 / 0.07
                     and the bingo page looked like the pattern had failed to
                     load. -->
                <g :id="`bg-unit-${name}`">
                    <!-- Board squares: the tile the game world is actually
                         divided into, and the square a bingo card and an S&L
                         board are both made of. A gap between them and a bevel
                         lit from the top left — the same light direction as
                         the panel edges in app.css — so it reads as a board
                         rather than as graph paper. -->
                    <template v-if="name === 'tiles'">
                        <rect x="4" y="4" width="38" height="38" rx="3" fill="currentColor" fill-opacity="0.1" />
                        <rect
                            x="4.5" y="4.5" width="37" height="37" rx="3"
                            fill="none" stroke="currentColor" stroke-opacity="0.38"
                        />
                        <path d="M5.5,41 L5.5,5.5 L41,5.5" fill="none" stroke="currentColor" stroke-opacity="0.55" />
                    </template>

                    <!-- The bingo card's diamond lattice. Five placements per
                         tile: one whole diamond in the middle and four
                         quarters at the corners, which is what makes the
                         rectangle repeat seamlessly over a lattice that is
                         really a checkerboard. -->
                    <template v-else-if="name === 'bingo'">
                        <path :d="diamond(19)" fill="currentColor" fill-opacity="0.11" />
                        <path :d="diamond(19)" fill="none" stroke="currentColor" stroke-opacity="0.5" />
                        <path :d="diamond(7)" fill="currentColor" fill-opacity="0.22" />
                    </template>

                    <!-- A Snakes and Ladders board: cells, one ladder, one
                         snake. Both stay wholly inside the tile — a shape that
                         crossed the edge would have to be drawn twice, once on
                         each side, for the repeat to close. -->
                    <template v-else>
                        <rect
                            v-for="cell in ladderCells" :key="`c-${cell.x}-${cell.y}`"
                            :x="cell.x" :y="cell.y" width="48" height="48" rx="3"
                            fill="currentColor" fill-opacity="0.095"
                            stroke="currentColor" stroke-opacity="0.4"
                        />
                        <g fill="none" stroke="currentColor" stroke-opacity="0.68" stroke-linecap="round">
                            <path v-for="(d, i) in ladder" :key="`l-${i}`" :d="d" />
                        </g>
                        <path
                            d="M196,26 C216,52 176,66 190,92 C202,114 166,124 174,152 C180,172 158,180 146,190"
                            fill="none" stroke="currentColor" stroke-opacity="0.57"
                            stroke-width="2.5" stroke-linecap="round"
                        />
                        <circle cx="196" cy="26" r="4" fill="currentColor" fill-opacity="0.68" />
                    </template>
                </g>

                <!-- What an accent lights up. Its own shape rather than the
                     unit above, because on the S&L tile the unit is sixteen
                     cells, a ladder and a snake — lighting all of that at once
                     is a flash, not a claimed square.

                     An outline over a soft fill rather than a solid block: at
                     full fill it read as a hole punched in the page and pulled
                     the eye straight off the heading. -->
                <g :id="`bg-mark-${name}`">
                    <template v-if="name === 'bingo'">
                        <path :d="diamond(19)" fill="currentColor" fill-opacity="0.4" />
                        <path :d="diamond(19)" fill="none" stroke="currentColor" stroke-opacity="0.9" />
                    </template>
                    <template v-else>
                        <rect
                            x="4" y="4" :width="markSize" :height="markSize" rx="3"
                            fill="currentColor" fill-opacity="0.4"
                        />
                        <rect
                            x="4.5" y="4.5" :width="markSize - 1" :height="markSize - 1" rx="3"
                            fill="none" stroke="currentColor" stroke-opacity="0.9"
                        />
                    </template>
                </g>

                <pattern
                    :id="`bg-pattern-${name}`"
                    :width="shape.tileW"
                    :height="shape.tileH"
                    patternUnits="userSpaceOnUse"
                >
                    <use
                        v-for="(spot, i) in shape.placements"
                        :key="i"
                        :href="`#bg-unit-${name}`"
                        :x="spot.x"
                        :y="spot.y"
                    />
                </pattern>
            </defs>

            <g class="app-bg__drift">
                <!--
                    Faded here rather than on the container, so the accents can
                    be brighter than the field without the container's opacity
                    flattening them back into it.

                    A hero page is mostly open space and carries the pattern at
                    full strength. Anywhere else the screen is already full of
                    cards and tables, and the same strength reads as clutter
                    behind them — so those get roughly half.

                    Oversized and offset by a whole tile because the drift below
                    moves this group, not the pattern inside it: at exactly
                    100% the trailing edge would walk into view.
                -->
                <rect
                    :class="hero
                        ? 'opacity-[0.13] dark:opacity-[0.11]'
                        : 'opacity-[0.07] dark:opacity-[0.06]'"
                    :x="-shape.tileW"
                    :y="-shape.tileH"
                    width="140%"
                    height="140%"
                    :fill="`url(#bg-pattern-${name})`"
                />

                <!-- A handful of squares that fade up and back down again: a
                     tile being claimed. They sit on the same lattice as the
                     pattern, so each lands exactly on a square that is already
                     there instead of beside it. -->
                <use
                    v-for="(spot, i) in accents"
                    :key="`accent-${i}`"
                    class="app-bg__accent text-amber-600 dark:text-amber-300"
                    :href="`#bg-mark-${name}`"
                    :x="spot.x"
                    :y="spot.y"
                    :style="{ animationDelay: `${spot.delay}s` }"
                />
            </g>
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // 'tiles' | 'bingo' | 'ladder'. An unknown value falls back to 'tiles'
    // rather than rendering nothing — this is decoration, and a typo in the
    // page map should not leave a blank layer nobody notices is missing.
    motif: { type: String, default: 'tiles' },
    // A hero page can carry the pattern at full strength; a page that already
    // has content in the top band cannot. See the rect's own comment.
    hero: { type: Boolean, default: true },
});

const SHAPES = {
    tiles: {
        tileW: 46,
        tileH: 46,
        placements: [{ x: 0, y: 0 }],
        step: 46,
        // Lattice coordinates, scattered by hand rather than randomly so no
        // two ever touch. A random scatter is only free of clumps by luck,
        // and this one also has to come out identical on the server and the
        // client or hydration reshuffles it.
        accents: [[1, 2], [6, 0], [4, 5], [9, 3], [13, 7], [7, 10], [16, 4], [19, 9], [11, 12], [22, 2], [2, 11], [24, 12]],
    },
    bingo: {
        tileW: 56,
        tileH: 56,
        placements: [{ x: 0, y: 0 }, { x: 56, y: 0 }, { x: 0, y: 56 }, { x: 56, y: 56 }, { x: 28, y: 28 }],
        step: 28,
        // Only cells whose coordinates sum to an even number exist on a
        // checkerboard lattice; an odd pair would light up empty space.
        accents: [[2, 2], [8, 0], [5, 5], [11, 3], [15, 7], [8, 10], [18, 4], [21, 9], [12, 12], [24, 2], [3, 11], [26, 12]],
    },
    ladder: {
        tileW: 232,
        tileH: 232,
        placements: [{ x: 0, y: 0 }],
        step: 58,
        accents: [[1, 2], [5, 0], [3, 5], [8, 3], [12, 7], [6, 10], [15, 4], [18, 9], [10, 12], [20, 2], [2, 11], [22, 12]],
    },
};

const name = computed(() => (props.motif in SHAPES ? props.motif : 'tiles'));
const shape = computed(() => SHAPES[name.value]);

const markSize = computed(() => (name.value === 'ladder' ? 48 : 38));

/** A diamond around the origin, so the four corner quarters clip themselves. */
function diamond(r) {
    return `M0,${-r}L${r},0L0,${r}L${-r},0Z`;
}

const ladderCells = Array.from({ length: 16 }, (unused, i) => ({
    x: (i % 4) * 58 + 5,
    y: Math.floor(i / 4) * 58 + 5,
}));

/** Two rails and five rungs, along a 45-degree run of two cells. */
const ladder = (() => {
    const from = [29, 203];
    const to = [145, 87];
    const perp = [5.66, 5.66];
    const at = (t) => [from[0] + (to[0] - from[0]) * t, from[1] + (to[1] - from[1]) * t];
    const line = (a, b) => `M${a[0]},${a[1]}L${b[0]},${b[1]}`;

    const rails = [1, -1].map((side) => line(
        [from[0] + perp[0] * side, from[1] + perp[1] * side],
        [to[0] + perp[0] * side, to[1] + perp[1] * side],
    ));

    const rungs = [0.1, 0.3, 0.5, 0.7, 0.9].map((t) => {
        const [x, y] = at(t);

        return line([x + perp[0], y + perp[1]], [x - perp[0], y - perp[1]]);
    });

    return [...rails, ...rungs];
})();

const accents = computed(() => shape.value.accents.map(([i, j], index) => ({
    x: i * shape.value.step,
    y: j * shape.value.step,
    // Spread far enough apart that no two are ever up at the same time.
    delay: index * 2.2,
})));
</script>

<style scoped>
.app-bg {
    position: fixed;
    /* Wider than the viewport by more than one tile in every direction, so the
       drift below never exposes an edge. */
    inset: -140px;
    /* Behind the torch-light pools of .landing-chrome (z-index -1), which are
       lighting and belong on top of what they light. */
    z-index: -2;
    pointer-events: none;
    contain: layout paint;
    /* Centred on the top of the VIEWPORT, not the top of this element: the
       inset above lifts the element 140px higher, and `at 50% 0` put the
       brightest part of the field off-screen and started the fade a third of
       the way down the hero. */
    -webkit-mask-image: radial-gradient(ellipse 115% 80% at 50% 140px, #000 12%, transparent 78%);
    mask-image: radial-gradient(ellipse 115% 80% at 50% 140px, #000 12%, transparent 78%);
}

.app-bg__field {
    /* The tiles are drawn in user units, so without a scale a phone gets the
       same squares as a desktop — four of them across the screen, which is a
       layout rather than a texture. Scaling the whole field is the only way to
       change that without a second set of geometry.
       Drawn at the inverse size first, so the scaled result lands back on
       exactly the container and no corner is left uncovered. */
    width: calc(100% / var(--bg-scale, 1));
    height: calc(100% / var(--bg-scale, 1));
    transform: scale(var(--bg-scale, 1));
    transform-origin: 0 0;
}

.app-bg__drift {
    animation: bg-drift 90s linear infinite;
    will-change: transform;
}

@media (max-width: 640px) {
    .app-bg__field {
        --bg-scale: 0.62;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .app-bg__field {
        --bg-scale: 0.82;
    }
}

/* Exactly one tile per cycle, which is what makes the loop invisible. */
@keyframes bg-drift {
    from {
        transform: translate3d(0, 0, 0);
    }

    to {
        transform: translate3d(calc(-1 * var(--bg-tile-w)), calc(-1 * var(--bg-tile-h)), 0);
    }
}

.app-bg__accent {
    opacity: 0;
    animation: bg-claim 26s ease-in-out infinite;
}

@keyframes bg-claim {
    0%,
    100% {
        opacity: 0;
    }

    10%,
    22% {
        opacity: var(--bg-accent-peak);
    }

    34% {
        opacity: 0;
    }
}

/* The field itself stays — it is a texture, and a still texture asks nothing
   of anybody. Only the two things that move stop. */
@media (prefers-reduced-motion: reduce) {
    .app-bg__drift,
    .app-bg__accent {
        animation: none;
    }

    .app-bg__accent {
        opacity: 0;
    }
}
</style>
