import { describe, expect, it } from 'vitest';

import { cellCentre, linesFor, openLinesThrough, strokesFor } from '@/Support/bingoLines';

/**
 * The card's line rules, client side.
 *
 * These MUST agree with BingoService::lines() — a card that highlights a
 * diagonal the standings do not score is a page arguing with its own
 * leaderboard, and the two implementations sit in different languages with
 * nothing forcing them together but this file and its PHP twin
 * (tests/Feature/BingoWinLinesTest.php). The counts asserted here are
 * asserted there too, on purpose.
 */
describe('linesFor', () => {
    it('has a row, a column and two diagonals on a 3x3 by default', () => {
        // 3 rows + 3 columns + 2 diagonals.
        expect(linesFor(3)).toHaveLength(8);
    });

    it('scales with the grid', () => {
        expect(linesFor(5)).toHaveLength(12);
        expect(linesFor(10)).toHaveLength(22);
    });

    it('numbers a row left to right', () => {
        expect(linesFor(3, ['ROW'])[0]).toEqual([0, 1, 2]);
        expect(linesFor(3, ['ROW'])[2]).toEqual([6, 7, 8]);
    });

    it('numbers a column top to bottom', () => {
        expect(linesFor(3, ['COLUMN'])[0]).toEqual([0, 3, 6]);
    });

    it('runs both diagonals corner to corner', () => {
        expect(linesFor(3, ['DIAGONAL'])).toEqual([[0, 4, 8], [2, 4, 6]]);
    });

    /** The whole point of the setting: a rows-only card has no diagonals. */
    it('leaves out the shapes the card does not count', () => {
        expect(linesFor(5, ['ROW'])).toHaveLength(5);
        expect(linesFor(5, ['DIAGONAL'])).toHaveLength(2);
        expect(linesFor(5, ['ROW', 'COLUMN'])).toHaveLength(10);
    });

    /**
     * Guarded because the form refuses to submit an empty set, but if one
     * ever arrives the honest answer is "no line can be completed" rather
     * than a crash or a silent fallback to all three.
     */
    it('has no lines at all when nothing counts', () => {
        expect(linesFor(5, [])).toEqual([]);
    });

    /** Every position on a line is on the grid. */
    it('never runs off the board', () => {
        for (const size of [3, 5, 7, 10]) {
            for (const line of linesFor(size)) {
                expect(line).toHaveLength(size);
                expect(Math.min(...line)).toBeGreaterThanOrEqual(0);
                expect(Math.max(...line)).toBeLessThan(size * size);
            }
        }
    });
});

describe('openLinesThrough', () => {
    /**
     * The reported bug in one assertion: a centre square sits on four lines
     * and the hint used to show one of them.
     */
    it('returns every line through the centre of a 5x5', () => {
        expect(openLinesThrough(12, 5, [])).toHaveLength(4);
    });

    it('returns two through a corner', () => {
        // Row, column, and the corner-to-corner diagonal.
        expect(openLinesThrough(0, 5, [])).toHaveLength(3);
    });

    it('returns two through an edge square that is on no diagonal', () => {
        expect(openLinesThrough(1, 5, [])).toHaveLength(2);
    });

    /** A line you have already finished is not a suggestion. */
    it('drops a line that is already complete', () => {
        const topRow = [0, 1, 2, 3, 4];

        const open = openLinesThrough(0, 5, topRow);

        expect(open).toHaveLength(2);
        expect(open).not.toContainEqual(topRow);
    });

    it('honours the card"s chosen shapes', () => {
        expect(openLinesThrough(12, 5, [], ['ROW'])).toHaveLength(1);
        expect(openLinesThrough(12, 5, [], ['DIAGONAL'])).toHaveLength(2);
    });

    it('accepts held positions as a Set or an array', () => {
        expect(openLinesThrough(0, 3, new Set([0, 1, 2]))).toHaveLength(2);
        expect(openLinesThrough(0, 3, [0, 1, 2])).toHaveLength(2);
    });
});

describe('cellCentre', () => {
    it('puts the first cell of a 5x5 at a tenth in', () => {
        expect(cellCentre(0, 5)).toEqual({ x: 10, y: 10 });
    });

    it('puts the last cell of a 5x5 at nine tenths', () => {
        expect(cellCentre(24, 5)).toEqual({ x: 90, y: 90 });
    });

    it('reads position row-major', () => {
        // Position 5 on a 5-wide grid is the start of the second row.
        expect(cellCentre(5, 5)).toEqual({ x: 10, y: 30 });
    });
});

describe('strokesFor', () => {
    it('draws one stroke per line', () => {
        const lines = openLinesThrough(12, 5, []);

        expect(strokesFor(lines, 5, [])).toHaveLength(lines.length);
    });

    /** "All of them" must not cost you "which one first". */
    it('marks exactly one stroke as nearest, even on a tie', () => {
        const lines = openLinesThrough(12, 5, []);

        const nearest = strokesFor(lines, 5, []).filter((s) => s.nearest);

        expect(nearest).toHaveLength(1);
    });

    it('marks the line closest to finishing', () => {
        // Four of the top row already held, so that row needs one more while
        // every other line through position 0 needs five.
        const held = [0, 1, 2, 3];
        const lines = openLinesThrough(0, 5, held);

        const strokes = strokesFor(lines, 5, held);
        const nearestIndex = strokes.findIndex((s) => s.nearest);

        expect(lines[nearestIndex]).toEqual([0, 1, 2, 3, 4]);
    });

    it('draws nothing when there is nothing to draw', () => {
        expect(strokesFor([], 5, [])).toEqual([]);
    });

    /** A one-square "line" cannot be a stroke; a 1x1 grid is not a card. */
    it('skips lines too short to draw', () => {
        expect(strokesFor([[3]], 5, [])).toEqual([]);
    });
});
