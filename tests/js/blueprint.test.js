import { describe, expect, it } from 'vitest';

import { blueprintPatch, decidesType, layoutFits, summariseBlueprint } from '@/Support/blueprint';

/**
 * Reading a blueprint, and applying one.
 *
 * Both halves are about restraint. The summary exists so a card says what you
 * are about to get rather than just naming it — but a chip for every stored
 * key would be a wall of text on a card. And applying one must fill in what
 * the template carries and nothing else, because a title-only template is
 * still an ordinary suggestion and half-configuring somebody's form from one
 * is worse than doing nothing.
 */
describe('summariseBlueprint', () => {
    it('describes a bingo card in the order you would read it', () => {
        const chips = summariseBlueprint({
            settings: {
                mode: 'TEAM',
                bingo_size: 4,
                win_condition: 'FULL_HOUSE',
                requires_approval: true,
            },
        });

        expect(chips).toEqual([
            't:blueprints.mode_team',
            't:blueprints.card',
            't:blueprints.win_full_house',
            't:blueprints.reviewed',
        ]);
    });

    it('describes a board by its grid and its roll limit', () => {
        const chips = summariseBlueprint({
            settings: { mode: 'SOLO', size: 'SIZE_7X7', dice_roll_limit: 2 },
        });

        expect(chips).toEqual([
            't:blueprints.mode_solo',
            't:blueprints.grid',
            't:blueprints.roll_limit',
        ]);
    });

    /**
     * A card nobody checks is the surprising one, so it is worth saying —
     * which means the false case needs a chip of its own rather than an
     * absence that reads the same as "not carried".
     */
    it('says so in both directions about reviewing', () => {
        expect(summariseBlueprint({ settings: { requires_approval: false } }))
            .toEqual(['t:blueprints.not_reviewed']);
        expect(summariseBlueprint({ settings: { requires_approval: true } }))
            .toEqual(['t:blueprints.reviewed']);
        // Not carried at all is not a claim either way.
        expect(summariseBlueprint({ settings: {} })).toEqual([]);
    });

    /** Open and listed is the default, so saying it would be noise. */
    it('stays quiet about the ordinary case', () => {
        expect(summariseBlueprint({ settings: { access_mode: 'OPEN', is_listed: true } })).toEqual([]);
    });

    it('flags access that is narrower than open', () => {
        expect(summariseBlueprint({ settings: { access_mode: 'GUILD' } }))
            .toEqual(['t:blueprints.access_guild']);
        expect(summariseBlueprint({ settings: { access_mode: 'INVITE', is_listed: false } }))
            .toEqual(['t:blueprints.access_invite', 't:blueprints.unlisted']);
    });

    /**
     * The chip that changes the decision. A format carrying the board is a
     * different proposition from one carrying a grid size — it is the evening
     * a host would otherwise spend.
     */
    it('says when a format brings the board with it', () => {
        expect(summariseBlueprint({ settings: { bingo_size: 5 }, layoutCount: 24 }))
            .toEqual(['t:blueprints.card', 't:blueprints.includes_board']);
    });

    it('stays quiet about a board that is not there', () => {
        expect(summariseBlueprint({ settings: { bingo_size: 5 }, layoutCount: 0 }))
            .toEqual(['t:blueprints.card']);
        expect(summariseBlueprint({ settings: { bingo_size: 5 } }))
            .toEqual(['t:blueprints.card']);
    });

    /** A title-only template is a real thing, and it has nothing to show. */
    it('returns nothing for a blueprint that carries nothing', () => {
        expect(summariseBlueprint({ title: 'Just a name' })).toEqual([]);
        expect(summariseBlueprint(null)).toEqual([]);
    });

    /** An unlimited roll limit is stored as null, and null is not a number. */
    it('does not claim a roll limit that is not there', () => {
        expect(summariseBlueprint({ settings: { dice_roll_limit: null } })).toEqual([]);
        expect(summariseBlueprint({ settings: { dice_roll_limit: 0 } })).toEqual([]);
    });
});

describe('decidesType', () => {
    it('knows when the template has already answered the type step', () => {
        expect(decidesType({ type: 'BINGO' })).toBe(true);
        expect(decidesType({ title: 'Just a name' })).toBe(false);
        expect(decidesType(null)).toBe(false);
    });
});

describe('blueprintPatch', () => {
    it('carries the columns and the settings together', () => {
        const patch = blueprintPatch({
            title: 'Clan Bingo Night',
            type: 'BINGO',
            metric: null,
            description: 'Two days, one card.',
            settings: { bingo_size: 4, win_condition: 'LINE' },
        });

        expect(patch).toEqual({
            title: 'Clan Bingo Night',
            type: 'BINGO',
            description: 'Two days, one card.',
            bingo_size: 4,
            win_condition: 'LINE',
        });
    });

    /**
     * The reason a title-only template is safe to click: it sets a title and
     * leaves everything the person may already have filled in alone.
     */
    it('sets only what the template actually carries', () => {
        expect(blueprintPatch({ title: 'Just a name' })).toEqual({ title: 'Just a name' });
    });

    /** null means "not carried", never "set this field to null". */
    it('never writes a null over something', () => {
        const patch = blueprintPatch({
            title: 'Sparse',
            type: null,
            settings: { dice_roll_limit: null, size: 'SIZE_5X5' },
        });

        expect(patch).toEqual({ title: 'Sparse', size: 'SIZE_5X5' });
        expect('dice_roll_limit' in patch).toBe(false);
    });

    it('is empty for no blueprint at all', () => {
        expect(blueprintPatch(null)).toEqual({});
    });
});

describe('layoutFits', () => {
    /**
     * A layout is a snapshot of one grid. Changing the size after picking a
     * template means the server drops whatever falls off the end — worth
     * saying before somebody finds a half-empty board.
     */
    it('knows when a board no longer matches the chosen size', () => {
        const board = { layoutCount: 20, settings: { size: 'SIZE_7X7' } };

        expect(layoutFits(board, 'SIZE_7X7', 5)).toBe(true);
        expect(layoutFits(board, 'SIZE_5X5', 5)).toBe(false);
    });

    it('checks a bingo card against the card size', () => {
        const card = { layoutCount: 12, settings: { bingo_size: 5 } };

        expect(layoutFits(card, 'SIZE_5X5', 5)).toBe(true);
        expect(layoutFits(card, 'SIZE_5X5', 3)).toBe(false);
    });

    /** Nothing to trim is nothing to warn about. */
    it('is satisfied by a template that carries no board', () => {
        expect(layoutFits({ layoutCount: 0, settings: { size: 'SIZE_9X9' } }, 'SIZE_5X5', 5)).toBe(true);
        expect(layoutFits(null, 'SIZE_5X5', 5)).toBe(true);
    });

    /** A layout with no size recorded cannot be judged, so it is not judged. */
    it('does not invent a mismatch it cannot know about', () => {
        expect(layoutFits({ layoutCount: 4, settings: {} }, 'SIZE_5X5', 5)).toBe(true);
    });
});
