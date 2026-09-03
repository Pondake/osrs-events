/**
 * The first-run tour's snooze, shared because two places need the same key.
 *
 * Closing the modal by Escape, the X or a click outside hides it for a day
 * rather than forever (AppRoot owns that). "Replay the intro" on the profile
 * page clears the server flag, but that alone did nothing while a snooze was
 * still standing — the button looked broken to anyone who had just dismissed
 * the tour, which is exactly who presses it.
 *
 * localStorage rather than sessionStorage because a day has to survive
 * closing the tab, and rather than a cookie because the server has no use
 * for it — it would ride along on every single request for nothing.
 */
const SNOOZE_KEY = 'onboarding-snoozed-until';
const SNOOZE_MS = 24 * 60 * 60 * 1000;

export function onboardingSnoozedUntil() {
    if (typeof window === 'undefined') return 0;

    try {
        return Number(window.localStorage.getItem(SNOOZE_KEY)) || 0;
    } catch (error) {
        // Private mode and blocked storage both throw on access rather than
        // returning null. Treating that as "not snoozed" shows the tour,
        // which is the harmless direction to fail in.
        console.error(error);

        return 0;
    }
}

export function snoozeOnboarding() {
    if (typeof window === 'undefined') return;

    try {
        window.localStorage.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_MS));
    } catch (error) {
        console.error(error);
    }
}

export function clearOnboardingSnooze() {
    if (typeof window === 'undefined') return;

    try {
        window.localStorage.removeItem(SNOOZE_KEY);
    } catch (error) {
        console.error(error);
    }
}
