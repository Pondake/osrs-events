/**
 * Whether to offer the in-app "turn notifications on" bar, and for how long
 * to stay quiet after somebody says no.
 *
 * **Why this exists at all:** the silent opt-in asks the browser directly,
 * unprompted, and that only reliably works on Chromium. Firefox has required
 * a user gesture for `Notification.requestPermission()` since 72 and ignores
 * the call otherwise; Safari the same; and Chrome may answer with its quiet
 * UI — a small bell in the address bar rather than a dialog — which is
 * indistinguishable from nothing having happened.
 *
 * So the automatic ask stays (it is genuinely the nicest experience where it
 * works), and this decides when to show a bar the user can *click*, which
 * produces a real gesture and therefore a real prompt on every platform. On
 * iOS it is the only route that exists.
 *
 * A pure function on purpose: every input is a fact somebody else established,
 * which is what makes the eight-way decision testable without a browser.
 */

/** Snoozed, not dismissed forever — see `snooze` below. */
export const SNOOZE_KEY = 'osrs-events:push-offer-snoozed-until';

/**
 * A week.
 *
 * "Not now" is an answer about right now, not about ever. Forever belongs to
 * the browser's own block and to the off switch in settings, both of which
 * are explicit; a bar that never returns after one dismissal would quietly
 * strand somebody who meant "later".
 */
export const SNOOZE_MS = 7 * 24 * 60 * 60 * 1000;

/**
 * @param {object} state
 * @param {boolean} state.signedIn        nobody is notified about nothing
 * @param {boolean} state.supported       the Push API exists here
 * @param {boolean} state.configured      the server has VAPID keys
 * @param {string}  state.permission      'default' | 'granted' | 'denied'
 * @param {boolean} state.optedOut        they switched notifications off
 * @param {boolean} state.isIos
 * @param {boolean} state.isStandalone    installed to the home screen
 * @param {number}  state.snoozedUntil    epoch ms, 0 when never snoozed
 * @param {number}  state.now
 * @param {boolean} state.settled         the automatic attempt has finished
 * @param {boolean} state.onBlockingPage  a page already asking for something
 * @param {boolean} state.hasChrome       the page has a header stack to sit in
 */
export function shouldOfferPush(state) {
    // Ordered cheapest-and-most-final first. Every one of these is a state
    // where the bar could not help, and a bar that cannot help is worse than
    // no bar: it advertises a button that does nothing.
    if (!state.signedIn || !state.supported || !state.configured) return false;

    // Granted needs no bar (auto-subscribe has it), denied cannot be undone
    // from a page, and opted out is a decision this must not argue with.
    if (state.permission !== 'default') return false;
    if (state.optedOut) return false;

    // iOS grants notifications only to an installed app. Offering the button
    // in Safari would produce a prompt that cannot be granted — the settings
    // page explains the install step in words instead.
    if (state.isIos && !state.isStandalone) return false;

    // Waiting for the automatic attempt matters: on Chromium it may raise the
    // real dialog, and stacking our bar underneath it asks the same question
    // twice at once.
    if (!state.settled) return false;

    if (state.onBlockingPage) return false;

    // The admin area and the lock screen bring their own full-height shells
    // and render no site chrome at all, so a bar emitted above them does not
    // push them down — it draws straight over their heading. Reported from
    // staging as the admin page wearing the bar across its own title.
    if (!state.hasChrome) return false;

    return state.snoozedUntil < state.now;
}

/**
 * When the bar may come back.
 *
 * Storage can throw outright rather than return null (private mode, blocked
 * storage), and the harmless direction to fail in is *showing* the bar — an
 * offer somebody has already seen is a smaller cost than one they can never
 * reach.
 */
export function snoozedUntil(storage, now = Date.now()) {
    try {
        const stored = Number(storage.getItem(SNOOZE_KEY)) || 0;

        // A stored time further out than the window itself is a clock change
        // or a hand-edited value; treat it as expired rather than locking the
        // bar away until 2043.
        return stored > now + SNOOZE_MS ? 0 : stored;
    } catch (error) {
        console.error(error);

        return 0;
    }
}

export function snooze(storage, now = Date.now()) {
    try {
        storage.setItem(SNOOZE_KEY, String(now + SNOOZE_MS));
    } catch (error) {
        console.error(error);
    }
}
