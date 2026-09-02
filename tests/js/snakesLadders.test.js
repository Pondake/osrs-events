import { describe, expect, it } from 'vitest';

import { bridgeParts, connection, endTiles, isSameRow, ladderParts, snakeParts, tileCenter, tileRect } from '@/Support/snakesLadders';

/** Every `<rect>` in a markup string, as numbers. */
function rects(markup) {
    return [...markup.matchAll(/<rect x="([-\d.]+)" y="([-\d.]+)" width="([\d.]+)" height="([\d.]+)"\/>/g)].map((m) => ({
        x: Number(m[1]),
        y: Number(m[2]),
        w: Number(m[3]),
        h: Number(m[4]),
    }));
}

const centre = (r) => ({ x: r.x + r.w / 2, y: r.y + r.h / 2 });

/**
 * Where a ray from a tile's centre through `p` leaves that tile — a plain
 * ray-versus-square intersection, written out here rather than borrowed from
 * the module, so these tests measure the overlap instead of restating how it
 * is computed.
 */
function exitPoint(position, cols, p) {
    const r = tileRect(position, cols);
    const c = { x: r.x + r.size / 2, y: r.y + r.size / 2 };
    const len = Math.hypot(p.x - c.x, p.y - c.y);
    const ux = (p.x - c.x) / len;
    const uy = (p.y - c.y) / len;
    const t = Math.min(
        ux === 0 ? Infinity : r.size / 2 / Math.abs(ux),
        uy === 0 ? Infinity : r.size / 2 / Math.abs(uy),
    );

    return { x: c.x + ux * t, y: c.y + uy * t };
}

/** How far the shape's end laps over its tile, measured along its own run. */
function lap(position, cols, p) {
    const e = exitPoint(position, cols, p);

    return Math.hypot(e.x - p.x, e.y - p.y);
}

describe('tileCenter', () => {
    // The board snakes: row 0 is the BOTTOM row and runs left to right, row 1
    // runs right to left. Getting this wrong draws every other row mirrored,
    // which looks plausible until you follow one connector across two rows.
    it('puts tile 1 bottom left', () => {
        expect(tileCenter(0, 5)).toEqual({ x: 10, y: 90 });
    });

    it('runs the bottom row left to right', () => {
        expect(tileCenter(4, 5)).toEqual({ x: 90, y: 90 });
    });

    it('turns around on the second row, so it carries on from the right', () => {
        expect(tileCenter(5, 5)).toEqual({ x: 90, y: 70 });
    });

    it('ends the last row of an odd-sided board on the right', () => {
        expect(tileCenter(24, 5)).toEqual({ x: 90, y: 10 });
    });
});

describe('connection', () => {
    it('has nothing to draw for a tile pointing at itself', () => {
        expect(connection(3, 3, 5)).toBeNull();
    });

    /**
     * The shape starts at the tile's EDGE and laps a fixed bit back over it,
     * rather than at the centre. That lapping bit is the only part drawn above
     * the grid, so if this drifts the connector either floats free of its tile
     * or sprouts from under the task text.
     */
    it('starts inside its own tile, whichever way it runs', () => {
        const from = tileRect(2, 5);

        for (const to of [13, 22, 4, 0, 24, 20]) {
            const { a } = connection(2, to, 5);

            expect(a.x).toBeGreaterThanOrEqual(from.x);
            expect(a.x).toBeLessThanOrEqual(from.x + from.size);
            expect(a.y).toBeGreaterThanOrEqual(from.y);
            expect(a.y).toBeLessThanOrEqual(from.y + from.size);
        }
    });

    /**
     * The overlap is measured along the connector's own direction, which is
     * why the edge is treated as a square rather than a circle. Measure it any
     * other way — straight down from the tile's top edge, say — and a diagonal
     * appears to lap less than a vertical, which is how "it looks smaller on
     * the diagonal ones" starts.
     */
    it('laps the same distance over its tile on a diagonal as on a straight run', () => {
        const straight = connection(2, 22, 5); // dead vertical
        const diagonal = connection(0, 18, 5); // across and up

        expect(lap(2, 5, straight.a)).toBeCloseTo(3.6, 5); // 18% of a 20-unit cell
        expect(lap(0, 5, diagonal.a)).toBeCloseTo(3.6, 5);
    });

    /** The end lands ON the tile's boundary, not somewhere near it. */
    it('measures that overlap from the tile edge itself', () => {
        const { a } = connection(2, 13, 5);
        const e = exitPoint(2, 5, a);
        const r = tileRect(2, 5);

        const onEdge = [r.x, r.x + r.size].some((v) => Math.abs(e.x - v) < 1e-9)
            || [r.y, r.y + r.size].some((v) => Math.abs(e.y - v) < 1e-9);

        expect(onEdge).toBe(true);
    });

    /**
     * Adjacent tiles get a stub rather than a full-length shape: both ends are
     * trimmed, so what is left spans the gap plus the two laps. Short, but
     * never empty — the two ends cannot meet, because the overlap is under
     * half a cell and two centres are never closer than one.
     */
    it('leaves a short but real run between neighbouring tiles', () => {
        const conn = connection(0, 1, 5);

        expect(conn.v.length).toBeGreaterThan(0);
        expect(conn.v.length).toBeLessThan(tileCenter(1, 5).x - tileCenter(0, 5).x);
        expect(conn.a.x).toBeLessThan(conn.b.x);
    });
});

describe('ladderParts', () => {
    it('gives every bar a lighter edge, which is what carries dark wood on a dark board', () => {
        const { base, edge } = ladderParts(connection(2, 13, 5));

        expect(rects(base).length).toBe(rects(edge).length);
        expect(rects(base).length).toBeGreaterThan(4);
    });

    it('spaces its rungs by cell size, so a longer ladder gets more of them', () => {
        const short = rects(ladderParts(connection(0, 2, 5)).base).length;
        const long = rects(ladderParts(connection(0, 4, 5)).base).length;

        expect(long).toBeGreaterThan(short);
    });

    it('is centred between its two anchor points', () => {
        const conn = connection(2, 13, 5);
        const { transform } = ladderParts(conn);
        const [x, y] = transform.match(/translate\(([-\d.]+) ([-\d.]+)\)/).slice(1).map(Number);

        expect(x).toBeCloseTo((conn.a.x + conn.b.x) / 2, 1);
        expect(y).toBeCloseTo((conn.a.y + conn.b.y) / 2, 1);
    });
});

describe('snakeParts', () => {
    /**
     * The `sin(πt)` envelope over the wave is what forces the lateral offset
     * back to zero at the far end — drop it and a snake finishes somewhere
     * beside its target tile, by an amount that changes with the length, which
     * reads as a rounding bug rather than a missing constraint.
     */
    it('runs from one anchor point to the other, whatever its length', () => {
        for (const [from, to] of [[23, 7], [24, 1], [7, 6], [22, 2]]) {
            const conn = connection(from, to, 5);
            const body = rects(snakeParts(conn).trunk);

            // First block sits on the head anchor; the last block before the
            // head square (which is appended after the loop) sits on the tail.
            expect(centre(body[0]).x).toBeCloseTo(conn.a.x, 1);
            expect(centre(body[0]).y).toBeCloseTo(conn.a.y, 1);
            expect(centre(body.at(-2)).x).toBeCloseTo(conn.b.x, 1);
            expect(centre(body.at(-2)).y).toBeCloseTo(conn.b.y, 1);
        }
    });

    it('tapers, so the shape says which way it goes without an arrowhead', () => {
        const body = rects(snakeParts(connection(23, 7, 5)).trunk);

        expect(body[0].w).toBeGreaterThan(body.at(-2).w * 2);
    });

    it('has a belly block for every body block, and two eyes', () => {
        const { trunk, belly, eyes } = snakeParts(connection(23, 7, 5));

        // One more trunk rect than belly rects: the head square.
        expect(rects(trunk).length).toBe(rects(belly).length + 1);
        expect(rects(eyes).length).toBe(2);
    });

    it('draws square eyes — a round one is what gives a pixel drawing away', () => {
        const [left] = rects(snakeParts(connection(23, 7, 5)).eyes);

        expect(left.w).toBe(left.h);
    });
});

describe('isSameRow', () => {
    /**
     * The one case a ladder cannot draw: every other connection on a
     * boustrophedon board rises visually, because a higher position is a
     * higher row. Only a jump along a row comes out horizontal, and a ladder
     * drawn flat reads as one that has fallen over.
     */
    it('spots a jump along a row, whichever direction that row runs', () => {
        expect(isSameRow(2, 4, 5)).toBe(true);   // bottom row, left to right
        expect(isSameRow(9, 5, 5)).toBe(true);   // second row, right to left
        expect(isSameRow(2, 13, 5)).toBe(false);
        expect(isSameRow(4, 5, 5)).toBe(false);  // the turn at the end of a row
    });
});

describe('bridgeParts', () => {
    it('sags away from the straight run, and comes back to its tiles', () => {
        const conn = connection(2, 4, 5);
        const deck = rects(bridgeParts(conn).base).map(centre);
        const straightAt = (t) => ({ x: conn.a.x + (conn.b.x - conn.a.x) * t, y: conn.a.y + (conn.b.y - conn.a.y) * t });
        const drop = (p) => Math.abs(p.y - straightAt(0.5).y);

        // The middle of the deck hangs clear of the straight line...
        expect(Math.max(...deck.map(drop))).toBeGreaterThan(1);

        // ...while the ends sit on the anchors, same envelope as the snake.
        // The deck is emitted two rails at a time, so the first pair straddles
        // `a` and the last pair straddles `b`; it is their midpoint that has
        // to land, not either rail on its own.
        const mid = (p, q) => ({ x: (p.x + q.x) / 2, y: (p.y + q.y) / 2 });
        const start = mid(deck[0], deck[1]);
        const end = mid(deck.at(-2), deck.at(-1));

        expect(start.x).toBeCloseTo(conn.a.x, 1);
        expect(start.y).toBeCloseTo(conn.a.y, 1);
        expect(end.x).toBeCloseTo(conn.b.x, 1);
        expect(end.y).toBeCloseTo(conn.b.y, 1);
    });

    /**
     * The sag used to follow the run's own perpendicular, which flips with
     * travel direction — so the same bridge hung down on a row read left to
     * right and arched up on the row above it. Rope sags.
     */
    it('always hangs downward, whichever way the row runs', () => {
        const lowest = (from, to) => {
            const conn = connection(from, to, 5);
            const deck = rects(bridgeParts(conn).base).map(centre);

            return Math.max(...deck.map((p) => p.y)) - Math.max(conn.a.y, conn.b.y);
        };

        expect(lowest(2, 4)).toBeGreaterThan(0);  // row runs left to right
        expect(lowest(9, 5)).toBeGreaterThan(0);  // row runs right to left
    });

    it('decks the span with planks that overhang its rails', () => {
        const { base, edge } = bridgeParts(connection(2, 4, 5));

        expect(rects(base).length).toBeGreaterThan(0);
        expect(rects(edge).length).toBeGreaterThan(0);
    });
});

describe('endTiles', () => {
    /**
     * One clip per connector, covering only its own two tiles. A single clip
     * over every end tile on the board would let a ladder surface on a snake's
     * tile just because it passes across it — which on a 9×9 board with eight
     * connectors happens by itself.
     */
    it('covers exactly the two tiles the connector belongs to', () => {
        expect(endTiles(2, 13, 5)).toEqual([tileRect(2, 5), tileRect(13, 5)]);
    });
});
