import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Where the two tile editors are mounted, which is not a style question.
 *
 * Both dialogs are `defineAsyncComponent`s, so the browser only fetches their
 * chunk when they are first rendered. Behind a `v-if` that flips on the click
 * that opens them, that fetch starts ON that click — the dialog then arrives a
 * round trip later, and a slow answer reads as a click that did nothing: no
 * error, no failed request, no Vue warning. That is exactly how it was
 * reported, on a board and on a card alike.
 *
 * Mounted closed with the other host dialogs, the chunk is already there when
 * the click happens. So the guard is: the editor may be gated on `canEdit`,
 * and on nothing that only becomes true when a tile is clicked.
 */
const PAGES = [
    {
        file: 'resources/js/Pages/BoardShow.vue',
        tag: 'tile-edit-modal',
        // The ref that says WHICH tile, and must not also say whether the
        // dialog exists.
        target: 'editingTile',
    },
    {
        file: 'resources/js/Pages/Events/Bingo.vue',
        tag: 'bingo-square-modal',
        target: 'editingSquare',
    },
];

/** The opening tag, from `<tag` to the `>` that ends it. */
function openingTag(source, tag) {
    const start = source.indexOf(`<${tag}`);

    expect(start, `${tag} is rendered at all`).toBeGreaterThan(-1);

    return source.slice(start, source.indexOf('>', start) + 1);
}

describe.each(PAGES)('$tag', ({ file, tag, target }) => {
    const source = readFileSync(resolve(process.cwd(), file), 'utf8');

    it('is not gated on the click that opens it', () => {
        expect(openingTag(source, tag)).not.toMatch(new RegExp(`v-if="${target}`));
    });

    /**
     * Mounted, but only for someone who may use it: a player would pay for a
     * chunk and a form they are never shown. `<template v-if="canEdit">` is
     * where the other host dialogs already live, so "inside it" is measured
     * as "after it opens, before the page's closing tags".
     */
    it('is mounted inside the host-only block', () => {
        const gate = source.indexOf('<template v-if="canEdit">');

        expect(gate, 'the host-only block exists').toBeGreaterThan(-1);
        expect(source.indexOf(`<${tag}`)).toBeGreaterThan(gate);
    });

    /**
     * Both formats say "you are editing this" with the same component and
     * switch the mode off the same way. They each had their own version — a
     * pill with a Done button on the board, a line of small text on the card
     * that could only be switched off again from the Manage menu — so the
     * same mode looked and ended differently depending on which event you
     * happened to be looking at.
     */
    it('announces the mode with the shared notice', () => {
        expect(source).toMatch(/<edit-mode-notice/);
        // The pill's own markup belongs to the component, nowhere else.
        expect(source).not.toMatch(/rounded-full bg-default ring-1 ring-primary/);
    });

    /** And the grid sits on the shared surface, which is where the box and
     *  the edit ring live. Neither belongs in a page. */
    it('puts the grid on the shared surface', () => {
        expect(source).toMatch(/<tile-surface/);
        expect(source).not.toMatch(/is-editing/);
        expect(source).not.toMatch(/border-stone-400/);
    });

    /**
     * Two refs, not one. With the dialog always mounted, "no tile chosen yet"
     * and "the dialog is closed" stop being the same state, so the open flag
     * has to be its own.
     */
    it('takes its open state from a ref of its own', () => {
        expect(openingTag(source, tag)).toMatch(/v-model:open="(tileEditOpen|squareModalOpen)"/);
    });
});
