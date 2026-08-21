/**
 * Which shapes count as a line, and which lines run through a square.
 *
 * Extracted from Pages/Events/Bingo.vue so it can be tested without mounting
 * a page full of @nuxt/ui components — and, more importantly, so there is
 * one place holding the rule the SERVER also implements
 * (BingoService::lines). Two copies of "what is a line" that disagree is a
 * card highlighting a diagonal the standings will not score.
 */

export const LINE_KINDS = ['ROW', 'COLUMN', 'DIAGONAL'];

/**
 * Every winning line on a card, as arrays of zero-based positions.
 *
 * @param {number} size  side length of the square grid
 * @param {string[]} kinds  which shapes this card counts
 * @returns {number[][]}
 */
export function linesFor(size, kinds = LINE_KINDS) {
    const lines = [];

    if (kinds.includes('ROW')) {
        for (let row = 0; row < size; row++) {
            lines.push(Array.from({ length: size }, (_, col) => row * size + col));
        }
    }

    if (kinds.includes('COLUMN')) {
        for (let col = 0; col < size; col++) {
            lines.push(Array.from({ length: size }, (_, row) => row * size + col));
        }
    }

    if (kinds.includes('DIAGONAL')) {
        lines.push(Array.from({ length: size }, (_, i) => i * size + i));
        lines.push(Array.from({ length: size }, (_, i) => i * size + (size - 1 - i)));
    }

    return lines;
}

/**
 * The unfinished lines running through one square.
 *
 * All of them, not the closest — a centre square sits on four, and showing
 * one silently hid the other three. A line already complete is not a
 * suggestion, so it drops out.
 *
 * @param {number} position  the square being hovered
 * @param {number} size
 * @param {Set<number>|number[]} held  positions this competitor already has
 * @param {string[]} kinds
 * @returns {number[][]}
 */
export function openLinesThrough(position, size, held, kinds = LINE_KINDS) {
    const mine = held instanceof Set ? held : new Set(held);

    return linesFor(size, kinds)
        .filter((line) => line.includes(position))
        .filter((line) => line.some((p) => !mine.has(p)));
}

/**
 * The centre of a cell, as a percentage of the grid — for drawing a line
 * across it in a 0-100 viewBox.
 *
 * The gaps between cells are ignored: at any real grid size that puts the
 * stroke a pixel or two off centre at the ends and nowhere else.
 */
export function cellCentre(position, size) {
    return {
        x: ((position % size) + 0.5) * (100 / size),
        y: (Math.floor(position / size) + 0.5) * (100 / size),
    };
}

/**
 * One stroke per line, with the nearest-to-finishing one marked so the
 * template can draw it a shade stronger — "all of them" should not cost you
 * "which one first".
 *
 * @returns {{x1: number, y1: number, x2: number, y2: number, nearest: boolean}[]}
 */
export function strokesFor(lines, size, held) {
    const drawable = lines.filter((line) => line.length >= 2);
    if (!drawable.length) return [];

    const mine = held instanceof Set ? held : new Set(held);
    const missing = (line) => line.filter((p) => !mine.has(p)).length;
    const fewest = Math.min(...drawable.map(missing));

    // Only the first line at the minimum is marked, so a tie still yields a
    // single emphasised answer rather than several.
    const nearestIndex = drawable.findIndex((line) => missing(line) === fewest);

    return drawable.map((line, index) => {
        const from = cellCentre(line[0], size);
        const to = cellCentre(line[line.length - 1], size);

        return { x1: from.x, y1: from.y, x2: to.x, y2: to.y, nearest: index === nearestIndex };
    });
}
