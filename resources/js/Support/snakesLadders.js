/**
 * Geometry and artwork for the snake/ladder overlay.
 *
 * All of it in the 0–100 space of the SVG's viewBox, positioned on the grid
 * element, so it scales with the fluid board without measuring pixels. The 4px
 * tile gap is folded into each cell's share — under two pixels of error.
 */

/**
 * Artwork colours — what the objects are made of, not brand or semantic
 * colour, so they stay out of `ui.config.ts`. The tile's own red/green ring
 * carries the meaning.
 *
 * Contrast against both board grounds: ladder 9.0:1 dark / 4.7:1 light, snake
 * 11.1:1 / 7.1:1. `woodLight` is what keeps dark wood readable on the dark
 * board, so it is not decoration.
 */
export const PALETTE = {
    wood: '#A16207',
    woodLight: '#E3B341',
    body: '#9F2B2B',
    belly: '#E7C9A9',
    eye: '#FDE047',
};

/** How far past a tile's edge the connector laps, as a share of a cell. */
const OVERLAP = 0.18;

/** How many blocks a snake's body is built from. */
const SNAKE_BLOCKS = 52;

const round = (n) => Math.round(n * 100) / 100;

/**
 * A tile's box in viewBox units. Boustrophedon: row 0 is the bottom row and
 * runs left to right, row 1 right to left. Same mapping as BoardShow's
 * orderedTiles, both derived from `position` rather than a rendered index.
 */
export function tileRect(position, cols) {
    const size = 100 / cols;
    const row = Math.floor(position / cols);
    const col = position % cols;
    const adjusted = row % 2 === 0 ? col : cols - 1 - col;

    return { x: adjusted * size, y: (cols - 1 - row) * size, size };
}

export function tileCenter(position, cols) {
    const r = tileRect(position, cols);

    return { x: r.x + r.size / 2, y: r.y + r.size / 2 };
}

function axis(a, b) {
    const dx = b.x - a.x;
    const dy = b.y - a.y;
    const length = Math.hypot(dx, dy);

    return { dx, dy, length, ux: dx / length, uy: dy / length, px: -dy / length, py: dx / length };
}

/**
 * Where the shape starts and stops: the edge of each end tile, lapped OVERLAP
 * of a cell back over it, so it grips the tiles rather than sprouting from
 * their middle. Null for a tile pointing at itself.
 */
export function connection(from, to, cols) {
    if (from === to) {
        return null;
    }

    const cell = 100 / cols;
    const A = tileCenter(from, cols);
    const B = tileCenter(to, cols);
    const v = axis(A, B);

    if (v.length === 0) {
        return null;
    }

    // A square, not a circle: the larger component decides which edge the ray
    // crosses, which keeps the overlap equal on a diagonal and a straight.
    // No short-connection guard is needed — OVERLAP is under half a cell and
    // two centres are never closer than one.
    const reach = cell / 2 / Math.max(Math.abs(v.ux), Math.abs(v.uy));
    const inset = Math.max(0, reach - cell * OVERLAP);

    const a = { x: A.x + v.ux * inset, y: A.y + v.uy * inset };
    const b = { x: B.x - v.ux * inset, y: B.y - v.uy * inset };

    return { a, b, v: axis(a, b), cell };
}

/**
 * A ladder: two rails and its rungs, each a bar with a lighter strip along one
 * edge. Built in a rotated group so that edge stays the same edge whichever
 * way the ladder runs.
 *
 * @returns {{base: string, edge: string, transform: string}}
 */
export function ladderParts({ a, b, v, cell }) {
    const angle = (Math.atan2(v.dy, v.dx) * 180) / Math.PI;
    const bar = cell * 0.1;
    const half = cell * 0.18;
    const lip = bar * 0.36;
    const base = [];
    const edge = [];

    for (const side of [-1, 1]) {
        const y = side * half - bar / 2;
        base.push(`<rect x="${round(-v.length / 2)}" y="${round(y)}" width="${round(v.length)}" height="${round(bar)}"/>`);
        edge.push(`<rect x="${round(-v.length / 2)}" y="${round(y)}" width="${round(v.length)}" height="${round(lip)}"/>`);
    }

    // Spaced by cell size, not a fixed count, so rung spacing looks the same
    // on a 5×5 board as on a 9×9 one.
    const rungs = Math.max(2, Math.round(v.length / (cell * 0.42)));

    for (let i = 0; i <= rungs; i++) {
        const x = -v.length / 2 + (v.length * i) / rungs - bar / 2;
        base.push(`<rect x="${round(x)}" y="${round(-half)}" width="${round(bar)}" height="${round(half * 2)}"/>`);
        edge.push(`<rect x="${round(x)}" y="${round(-half)}" width="${round(lip)}" height="${round(half * 2)}"/>`);
    }

    return {
        transform: `translate(${round((a.x + b.x) / 2)} ${round((a.y + b.y) / 2)}) rotate(${round(angle)})`,
        base: base.join(''),
        edge: edge.join(''),
    };
}

function spinePoint(a, v, t, amp, waves) {
    const wave = Math.sin(Math.PI * t) * Math.sin(2 * Math.PI * waves * t) * amp;

    return { x: a.x + v.dx * t + v.px * wave, y: a.y + v.dy * t + v.py * wave };
}

/**
 * A snake: squares along a winding spine, tapering to the tail, with a belly
 * run offset to one side and square eyes.
 *
 * The spine is a sine under a `sin(πt)` envelope; the envelope is what lands
 * the body exactly on both anchor points whatever the length.
 *
 * @returns {{trunk: string, belly: string, eyes: string}}
 */
export function snakeParts({ a, v, cell }) {
    const waves = Math.max(1, Math.round(v.length / (cell * 1.4)));
    const trunk = [];
    const belly = [];

    for (let i = 0; i <= SNAKE_BLOCKS; i++) {
        const t = i / SNAKE_BLOCKS;
        const c = spinePoint(a, v, t, cell * 0.2, waves);

        // The 0.6 exponent keeps it fat for most of its length rather than
        // thinning immediately, which is what makes it read as a body.
        const w = cell * (0.17 * (1 - t) ** 0.6 + 0.05);
        trunk.push(`<rect x="${round(c.x - w / 2)}" y="${round(c.y - w / 2)}" width="${round(w)}" height="${round(w)}"/>`);

        const bw = w * 0.4;
        const bx = c.x + v.px * w * 0.24;
        const by = c.y + v.py * w * 0.24;
        belly.push(`<rect x="${round(bx - bw / 2)}" y="${round(by - bw / 2)}" width="${round(bw)}" height="${round(bw)}"/>`);
    }

    const hw = cell * 0.28;
    trunk.push(`<rect x="${round(a.x - hw / 2)}" y="${round(a.y - hw / 2)}" width="${round(hw)}" height="${round(hw)}"/>`);

    const ew = cell * 0.06;
    const eyes = [-1, 1]
        .map((s) => {
            const x = a.x + v.px * cell * 0.06 * s + v.ux * cell * 0.06;
            const y = a.y + v.py * cell * 0.06 * s + v.uy * cell * 0.06;

            return `<rect x="${round(x - ew / 2)}" y="${round(y - ew / 2)}" width="${round(ew)}" height="${round(ew)}"/>`;
        })
        .join('');

    return { trunk: trunk.join(''), belly: belly.join(''), eyes };
}

/**
 * Two tiles on the same row — the one case a ladder cannot draw, because flat
 * it reads as one that has fallen over. Every other connection rises visually,
 * so this is a fact about the board rather than an angle threshold.
 */
export function isSameRow(from, to, cols) {
    return Math.floor(from / cols) === Math.floor(to / cols);
}

/**
 * A rope bridge for a jump along a row: a bowed deck between two sagging
 * rails, built from axis-aligned squares so the curve stays on style.
 */
export function bridgeParts({ a, b, v, cell }) {
    const bow = cell * 0.32;
    const half = cell * 0.2;
    const block = cell * 0.08;
    const samples = Math.max(24, Math.round(v.length / (cell * 0.06)));
    const base = [];
    const edge = [];

    // Always downward, never the run's own perpendicular - that flips with
    // travel direction, so the same bridge would arch on the row above.
    const sag = v.py >= 0 ? 1 : -1;

    // Same envelope as the snake's wave, so it lands on its tiles exactly.
    const at = (t) => {
        const s = Math.sin(Math.PI * t) * bow * sag;

        return { x: a.x + v.dx * t + v.px * s, y: a.y + v.dy * t + v.py * s };
    };

    const square = (into, p) =>
        into.push(`<rect x="${round(p.x - block / 2)}" y="${round(p.y - block / 2)}" width="${round(block)}" height="${round(block)}"/>`);

    // A rail block at every sample, so the rails read as rope and not dots.
    const plankEvery = Math.max(3, Math.round(samples / (v.length / (cell * 0.34))));

    for (let i = 0; i <= samples; i++) {
        const t = i / samples;
        const c = at(t);

        // Local perpendicular: the deck curves, so the rails follow its
        // tangent rather than the straight run.
        const ahead = at(Math.min(1, t + 0.01));
        const behind = at(Math.max(0, t - 0.01));
        const tx = ahead.x - behind.x;
        const ty = ahead.y - behind.y;
        const tl = Math.hypot(tx, ty) || 1;
        const px = -ty / tl;
        const py = tx / tl;

        for (const side of [-1, 1]) {
            square(base, { x: c.x + px * half * side, y: c.y + py * half * side });
        }

        if (i % plankEvery === 0) {
            // Planks overhang the rails, which is what separates this from a
            // ladder's flush rungs.
            for (let k = -1.15; k <= 1.15; k += 0.25) {
                square(edge, { x: c.x + px * half * k, y: c.y + py * half * k });
            }
        }
    }

    return { transform: '', base: base.join(''), edge: edge.join('') };
}

/**
 * The two tiles a connector belongs to, as clip rectangles for the top pass.
 *
 * One clip per connector, never one covering every end tile: a ladder crossing
 * somebody else's end tile has no business surfacing there.
 *
 * Plain objects, not markup strings — these become real `<rect>` elements,
 * because `v-html` into a `<clipPath>` yields nothing and an empty clip hides
 * everything it is applied to.
 */
export function endTiles(from, to, cols) {
    return [from, to].map((p) => tileRect(p, cols));
}

/** How many points a followed path is sampled into. */
const PATH_SAMPLES = 60;

/**
 * The line a piece travels when a connector carries it - the same centreline
 * the artwork is built on, so it rides the shape rather than cutting its own
 * route. From the tile centre, not the trimmed anchor: a piece leaves from
 * where it is standing.
 */
export function travelPath(from, to, cols, type) {
    const conn = connection(from, to, cols);

    if (conn === null) {
        return [];
    }

    const A = tileCenter(from, cols);
    const B = tileCenter(to, cols);
    const v = { dx: B.x - A.x, dy: B.y - A.y, px: conn.v.px, py: conn.v.py };

    // Only a snake winds, on the same sine its body is drawn with.
    const waves = type === 'SNAKE' ? Math.max(1, Math.round(conn.v.length / (conn.cell * 1.4))) : 0;
    const bow = type === 'LADDER' && isSameRow(from, to, cols) ? conn.cell * 0.32 * (conn.v.py >= 0 ? 1 : -1) : 0;

    const points = [];

    for (let i = 0; i <= PATH_SAMPLES; i++) {
        const t = i / PATH_SAMPLES;
        const envelope = Math.sin(Math.PI * t);
        const offset = waves > 0
            ? envelope * Math.sin(2 * Math.PI * waves * t) * conn.cell * 0.2
            : envelope * bow;

        points.push({ x: A.x + v.dx * t + v.px * offset, y: A.y + v.dy * t + v.py * offset });
    }

    return points;
}
