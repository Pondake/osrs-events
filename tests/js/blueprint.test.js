import { describe, expect, it } from 'vitest';

import { blueprintPatch, decidesType, summariseBlueprint } from '@/Support/blueprint';

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
