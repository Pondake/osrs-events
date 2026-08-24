import { describe, expect, it, vi } from 'vitest';

import { SNOOZE_MS, shouldOfferPush, snooze, snoozedUntil } from '@/Support/pushPrompt';

/**
 * When to offer the in-app "turn notifications on" bar.
 *
 * This exists because the automatic ask is not reliable: Firefox and Safari
 * ignore `Notification.requestPermission()` without a user gesture, and Chrome
 * may answer it with a bell in the address bar that looks exactly like nothing
 * having happened. Reported from staging as "it did not prompt me".
 *
 * The bar is the fix, and the interesting half is everywhere it must stay
 * hidden — a bar offering a button that cannot work is worse than no bar,
 * because it turns a quiet failure into a visible broken one.
 */

const base = {
    signedIn: true,
    supported: true,
    configured: true,
    permission: 'default',
    optedOut: false,
    isIos: false,
    isStandalone: false,
    snoozedUntil: 0,
    now: 1_000_000,
    settled: true,
    onBlockingPage: false,
    hasChrome: true,
};

describe('shouldOfferPush', () => {
    it('offers once the automatic attempt has finished without a decision', () => {
        expect(shouldOfferPush(base)).toBe(true);
    });

    /**
     * Not a nicety: on Chromium the automatic attempt may raise the real
     * dialog, and a bar asking the same question underneath it is asking
     * twice at once.
     */
    it('waits for the automatic attempt to settle', () => {
        expect(shouldOfferPush({ ...base, settled: false })).toBe(false);
    });

    it('stays hidden once the question has an answer', () => {
        expect(shouldOfferPush({ ...base, permission: 'granted' })).toBe(false);
        // Denied cannot be undone from a page — the settings page explains
        // where the browser's own control is instead.
        expect(shouldOfferPush({ ...base, permission: 'denied' })).toBe(false);
    });

    it('does not argue with somebody who switched notifications off', () => {
        expect(shouldOfferPush({ ...base, optedOut: true })).toBe(false);
    });

    /** A button that leads to a server with no keys is an advert, not a feature. */
    it('stays hidden when the server could not send anything anyway', () => {
        expect(shouldOfferPush({ ...base, configured: false })).toBe(false);
    });

    it('stays hidden where the browser has no Push API, and for signed-out visitors', () => {
        expect(shouldOfferPush({ ...base, supported: false })).toBe(false);
        expect(shouldOfferPush({ ...base, signedIn: false })).toBe(false);
    });

    /**
     * iOS grants notifications only to an installed app, so in Safari the
     * button would raise a prompt that cannot be granted. Installed, it is the
     * only route that has ever existed there.
     */
    it('waits for the install on iOS, then offers', () => {
        expect(shouldOfferPush({ ...base, isIos: true, isStandalone: false })).toBe(false);
        expect(shouldOfferPush({ ...base, isIos: true, isStandalone: true })).toBe(true);
    });

    /** The gate and the tour are already asking for something. */
    it('does not stack on a page that is already asking', () => {
        expect(shouldOfferPush({ ...base, onBlockingPage: true })).toBe(false);
    });

    /**
     * The admin area and the lock screen render no site chrome and bring
     * their own full-height shells, so a bar above them does not push them
     * down — it draws over their heading. Reported from staging.
     */
    it('stays out of pages that have nowhere to put it', () => {
        expect(shouldOfferPush({ ...base, hasChrome: false })).toBe(false);
    });

    it('respects a snooze, and returns after it', () => {
        expect(shouldOfferPush({ ...base, snoozedUntil: base.now + 1 })).toBe(false);
        expect(shouldOfferPush({ ...base, snoozedUntil: base.now - 1 })).toBe(true);
    });
});

describe('snooze', () => {
    function storage(initial = {}) {
        const values = { ...initial };

        return {
            values,
            getItem: (key) => values[key] ?? null,
            setItem: (key, value) => {
                values[key] = value;
            },
        };
    }

    it('comes back after a week rather than never', () => {
        const store = storage();

        snooze(store, 1_000);

        expect(snoozedUntil(store, 1_000)).toBe(1_000 + SNOOZE_MS);
        expect(snoozedUntil(store, 1_000 + SNOOZE_MS + 1)).toBeLessThan(1_000 + SNOOZE_MS + 1);
    });

    it('treats a never-snoozed browser as due', () => {
        expect(snoozedUntil(storage(), 1_000)).toBe(0);
    });

    /**
     * A stored time further out than the window itself means a clock change or
     * a hand-edited value. Honouring it would lock the bar away for years.
     */
    it('ignores a stored time that could not have come from here', () => {
        const store = storage({ 'osrs-events:push-offer-snoozed-until': String(1_000 + SNOOZE_MS * 50) });

        expect(snoozedUntil(store, 1_000)).toBe(0);
    });

    /**
     * Private mode and blocked storage throw on access rather than returning
     * null. Failing toward showing the bar is the harmless direction: an offer
     * somebody has already seen costs less than one they can never reach.
     */
    it('falls back to due when storage throws', () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});

        const hostile = {
            getItem: () => {
                throw new Error('denied');
            },
            setItem: () => {
                throw new Error('denied');
            },
        };

        expect(snoozedUntil(hostile, 1_000)).toBe(0);
        expect(() => snooze(hostile, 1_000)).not.toThrow();

        vi.restoreAllMocks();
    });
});
