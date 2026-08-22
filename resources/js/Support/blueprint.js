import { trans } from 'laravel-vue-i18n';

import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

/**
 * Turning a blueprint's stored settings into something you can read at a
 * glance.
 *
 * The whole point of showing templates as cards rather than as an
 * autocomplete is that you can see what you are about to get. That only works
 * if the card says "5×5, first line wins, host checks claims" — a name and a
 * type badge is the dropdown again with more padding.
 *
 * Kept out of the component so it can be tested without mounting a modal full
 * of @nuxt/ui, the same reason Support/bingoLines.js exists.
 */

/** A short line per setting worth knowing about, in reading order. */
export function summariseBlueprint(blueprint) {
    const settings = blueprint?.settings ?? {};
    const chips = [];

    if (settings.mode) {
        chips.push(trans(settings.mode === 'TEAM' ? 'blueprints.mode_team' : 'blueprints.mode_solo'));
    }

    // Snakes & Ladders.
    if (settings.size) {
        chips.push(trans('blueprints.grid', {
            size: BOARD_SIZE_LABEL[settings.size] ?? settings.size,
            tiles: BOARD_TILE_COUNT[settings.size] ?? '?',
        }));
    }

    if (settings.dice_roll_limit) {
        chips.push(trans('blueprints.roll_limit', { n: settings.dice_roll_limit }));
    }

    // Bingo. The card is square, so one number describes it.
    if (settings.bingo_size) {
        chips.push(trans('blueprints.card', { size: `${settings.bingo_size}×${settings.bingo_size}` }));
    }

    if (settings.win_condition) {
        chips.push(trans(settings.win_condition === 'FULL_HOUSE'
            ? 'blueprints.win_full_house'
            : 'blueprints.win_line'));
    }

    // Only worth saying when it is the answer people care about: a card
    // nobody checks is the surprising one, so both directions get a chip.
    if (settings.requires_approval === true) chips.push(trans('blueprints.reviewed'));
    if (settings.requires_approval === false) chips.push(trans('blueprints.not_reviewed'));

    if (settings.access_mode && settings.access_mode !== 'OPEN') {
        chips.push(trans(settings.access_mode === 'GUILD'
            ? 'blueprints.access_guild'
            : 'blueprints.access_invite'));
    }

    if (settings.is_listed === false) chips.push(trans('blueprints.unlisted'));

    // Last, and the one worth reading: a format that brings the board is a
    // different proposition from one that brings a grid size. It is the
    // evening a host would otherwise spend.
    if (blueprint?.layoutCount) {
        chips.push(trans('blueprints.includes_board', { n: blueprint.layoutCount }));
    }

    return chips;
}

/**
 * Whether picking this template settles the event type for you.
 *
 * A title-only blueprint is still an ordinary suggestion and leaves the type
 * step to be answered; one that carries a type has already answered it, and
 * the stepper can move on.
 */
export function decidesType(blueprint) {
    return Boolean(blueprint?.type);
}

/**
 * The form fields a blueprint fills in.
 *
 * Only what it actually carries: a title-only template sets a title and
 * leaves a half-configured form alone, which is what makes it safe to click
 * one out of curiosity. `null` is "not carried", not "set this to null".
 */
export function blueprintPatch(blueprint) {
    if (!blueprint) return {};

    const patch = { title: blueprint.title };

    if (blueprint.type) patch.type = blueprint.type;
    if (blueprint.metric) patch.metric = blueprint.metric;
    if (blueprint.description) patch.description = blueprint.description;

    for (const [key, value] of Object.entries(blueprint.settings ?? {})) {
        if (value !== null && value !== undefined) patch[key] = value;
    }

    return patch;
}

/**
 * Whether a template's board still fits the size the form is set to.
 *
 * A layout is a snapshot of one grid. Applying a 7x7 board to a 5x5 event
 * drops everything past the last square — the server does that rather than
 * stacking tiles, but the person choosing deserves to know before they find
 * a half-empty board.
 */
export function layoutFits(blueprint, size, bingoSize) {
    if (!blueprint?.layoutCount) return true;

    const saved = blueprint.settings ?? {};

    if (saved.size) return saved.size === size;
    if (saved.bingo_size) return saved.bingo_size === bingoSize;

    return true;
}
