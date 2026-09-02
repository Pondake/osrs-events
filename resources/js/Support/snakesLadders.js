/**
 * The snake/ladder overlay on a Snakes & Ladders board: where the shapes go,
 * and what they are made of.
 *
 * Everything works in the 0–100 percentage space the SVG's viewBox uses, so
 * it scales with the fluid grid for free — no pixel measuring, no
 * ResizeObserver. The overlay is positioned on the grid element itself, so
 * "50, 50" here really is the middle of the grid.
 *
 * The 4px gap between tiles is folded into each cell's share rather than
 * accounted for separately: on a 7×7 board 705px wide that misplaces a centre
 * by under two pixels.
 */

/**
 * The artwork's colours, chosen 2026-09-02 out of a five-palette comparison.
 *
 * Not in `ui.config.ts`: these are not brand or semantic colours, they are
 * what a wooden ladder and a snake are made of. The board's own red/green
 * still carries the meaning — a snake tile is outlined red and a ladder tile
 * green — which is exactly what frees these to look like objects instead of
 * repeating the signal a third time.
 *
 * Measured against the board's two grounds (#1c1917 dark, #fffbeb light):
 * the ladder clears 9.0:1 and 4.7:1, the snake 11.1:1 and 7.1:1.
 */
export const PALETTE = {
    // Light oak, with a lighter strip along one edge of every bar. That strip
    // is not decoration: a dark wood on the dark board lives or dies by it.
    wood: '#A16207',
    woodLight: '#E3B341',
    // A dusty adder rather than a signal red, with a cream belly.
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
 * A tile's box in viewBox units.
 *
 * Boustrophedon, like the board itself: row 0 is the bottom row and runs left
 * to right, row 1 runs right to left, and so on. Same mapping as BoardShow's
 * orderedTiles, kept in step by both deriving it from `position` rather than
 * from a rendered index.
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
 * Where the drawn shape starts and stops — and it is not the tile centres.
 *
 * The connector is pulled back to the edge of each end tile and then pushed
 * OVERLAP of a cell back over it, so it visibly grips the two tiles it belongs
 * to rather than sprouting from their middle. It stops under the task text
 * rather than over it — see .board-layers in app.css.
 *
 * Returns null when there is nothing to draw — a tile pointing at itself is
 * bad data, not a shape.
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

    // Distance from a centre to where the ray leaves the tile. A square, not
    // a circle, so the larger component decides which edge is crossed — that
    // is what keeps the overlap the same size on a diagonal as on a straight.
    //
    // The two ends can never meet, so there is no short-connection case to
    // guard: OVERLAP is under half a cell and the closest two tile centres
    // can be is one cell, which leaves the trimmed run positive for every
    // pair on a board. A guard for it was written here and then removed —
    // the test pinning it turned out to be unfalsifiable.
    const reach = cell / 2 / Math.max(Math.abs(v.ux), Math.abs(v.uy));
    const inset = Math.max(0, reach - cell * OVERLAP);

    const a = { x: A.x + v.ux * inset, y: A.y + v.uy * inset };
    const b = { x: B.x - v.ux * inset, y: B.y - v.uy * inset };

    return { a, b, v: axis(a, b), cell };
}

/**
 * A ladder: two rails and its rungs, each a solid bar with a lighter strip
 * along one edge.
 *
 * Built inside a rotated group so "one edge" stays the same edge whichever way
 * the ladder runs, and rendered with crisp edges — the blocks are the style,
 * and antialiasing them away turns it back into a smooth drawing.
 *
 * @returns {{base: string, edge: string, transform: string}} the two fill
 *   groups and the transform they share, so the caller decides the colours.
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

    // Spaced by cell size rather than by a fixed count: a long ladder gets more
    // rungs, and the spacing looks the same on a 5×5 board as on a 9×9 one.
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
 * A snake: squares along a winding spine, tapering to the tail, with a
 * narrower run of belly squares offset to one side, a square head, and square
 * eyes. Round eyes are the one thing that gives a pixel drawing away.
 *
 * The spine is a sine along the connection under a `sin(πt)` envelope. The
 * envelope is what forces the offset to zero at both ends: the body starts and
 * finishes exactly on its two anchor points however many waves fit between
 * them. A whole number of waves keeps it from leaning to one side.
 *
 * @returns {{trunk: string, belly: string, eyes: string}} three fill groups.
 */
export function snakeParts({ a, v, cell }) {
    const waves = Math.max(1, Math.round(v.length / (cell * 1.4)));
    const trunk = [];
    const belly = [];

    for (let i = 0; i <= SNAKE_BLOCKS; i++) {
        const t = i / SNAKE_BLOCKS;
        const c = spinePoint(a, v, t, cell * 0.2, waves);

        // Thick behind the head, thinning towards the tail. The 0.6 exponent
        // keeps it fat for most of its length instead of shrinking away
        // immediately, which is what makes it read as a body.
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
 * Two tiles on the same row — the one case where a ladder reads wrong.
 *
 * A ladder is a climbing object, so drawn flat it looks like a ladder that has
 * fallen over. Every other connection on a boustrophedon board rises visually,
 * because a higher position is a higher row; only a jump along a row is
 * horizontal. That makes this a fact about the board rather than an angle
 * threshold about pixels.
 */
export function isSameRow(from, to, cols) {
    return Math.floor(from / cols) === Math.floor(to / cols);
}

/**
 * A rope bridge: a bowed deck of planks between two sagging rails, for a jump
 * along a row.
 *
 * Built from axis-aligned squares rather than a rotated group, because the
 * deck curves — and squares are what the rest of this drawing is made of, so a
 * curve built out of them stays on style instead of turning into a smooth
 * path with blocky neighbours.
 */
export function bridgeParts({ a, b, v, cell }) {
    const bow = cell * 0.32;
    const half = cell * 0.2;
    const block = cell * 0.08;
    const samples = Math.max(24, Math.round(v.length / (cell * 0.06)));
    const base = [];
    const edge = [];

    // Always downward on screen, never with the run's own perpendicular: that
    // flips with travel direction, so the same bridge sagged on a board read
    // left-to-right and arched on the row above it. A rope sags.
    const sag = v.py >= 0 ? 1 : -1;

    // The deck's centreline, sagging away from the straight run and coming
    // back to zero at both ends — the same envelope the snake's wave uses, so
    // a bridge lands on its tiles as exactly as everything else does.
    const at = (t) => {
        const s = Math.sin(Math.PI * t) * bow * sag;

        return { x: a.x + v.dx * t + v.px * s, y: a.y + v.dy * t + v.py * s };
    };

    const square = (into, p) =>
        into.push(`<rect x="${round(p.x - block / 2)}" y="${round(p.y - block / 2)}" width="${round(block)}" height="${round(block)}"/>`);

    // One plank every so often, and a rail block at every sample so the two
    // rails read as continuous rope rather than as a dotted line.
    const plankEvery = Math.max(3, Math.round(samples / (v.length / (cell * 0.34))));

    for (let i = 0; i <= samples; i++) {
        const t = i / samples;
        const c = at(t);

        // Local perpendicular from a finite difference: the deck is curved, so
        // the rails have to follow its tangent and not the straight run.
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
            // Planks overhang the rails a little, the way decking does — it is
            // the clearest thing separating this from a ladder's flush rungs.
            for (let k = -1.15; k <= 1.15; k += 0.25) {
                square(edge, { x: c.x + px * half * k, y: c.y + py * half * k });
            }
        }
    }

    return { transform: '', base: base.join(''), edge: edge.join('') };
}

/**
 * The two tiles a connector belongs to, as clip rectangles.
 *
 * The overlay runs underneath the grid, so a connector is a hint behind the
 * tiles it crosses. Drawn a second time through this clip, its two ends come
 * back out on top — which is what makes it grip the tiles it is about instead
 * of emerging from under them.
 *
 * One clip per connector, never one covering every end tile on the board: a
 * ladder that happens to cross somebody else's end tile has no business
 * surfacing there, and on a 9×9 board with eight connectors that happens by
 * itself.
 *
 * Plain objects rather than the markup strings the shapes use, because these
 * go into a `<clipPath>` — and `v-html` into one of those silently produces
 * nothing, which clips the entire pass away. Every shape here stays a string;
 * only the clip has to be real elements.
 */
export function endTiles(from, to, cols) {
    return [from, to].map((p) => tileRect(p, cols));
}

/** How many points a followed path is sampled into. */
const PATH_SAMPLES = 60;

/**
 * The line a player piece travels when a connector carries it.
 *
 * The same centreline the artwork is built on, so the piece rides the snake's
 * body and the ladder's rungs instead of taking its own route between the same
 * two tiles. It runs from the tile centre, not from the trimmed anchor: the
 * shape starts at the tile's edge for looks, but a piece has to leave from
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

    // A ladder and a bridge are walked along their own line; only a snake
    // winds, and it winds on the same sine the body is drawn with.
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
