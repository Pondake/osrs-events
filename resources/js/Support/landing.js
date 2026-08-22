/**
 * Which pages wear the branding, and which stay plain.
 *
 * The site is two things at once. The landing and content pages are where
 * somebody decides whether to bother — they get the torch-lit panel look the
 * coming-soon page introduced. Everything else is where people run their
 * events, and decoration there is something to look past on the way to a form
 * they have opened forty times.
 *
 * Keyed on the Inertia component name, the same handle AppRoot already uses
 * to decide chrome — it is the one thing the shell reliably knows about the
 * page it is rendering.
 */
export const LANDING_PAGES = [
    'Home',
    'SnakesAndLadders',
    'OsrsClanEvents',
    'OsrsEventIdeas',
    // Every CMS page — about, privacy, terms, donate. They are read by the
    // same people, in the same frame of mind, and they are what a search
    // result lands on.
    'Page',
];

export function isLandingPage(component) {
    return LANDING_PAGES.includes(String(component ?? ''));
}

/**
 * The header links that still go somewhere while the site is locked.
 *
 * Only these, because a nav full of links that bounce straight back to the
 * lock screen is a menu of dead ends. This is the header's list, not the
 * lock's — EnsureSiteUnlocked decides by route name and lets every CMS page
 * through, including ones an admin adds later. The footer links to those and
 * needs no filtering; the header does not link to them at all.
 */
export const PUBLIC_PATHS = [
    '/',
    '/osrs-snakes-and-ladders',
    '/osrs-clan-events',
    '/osrs-event-ideas',
    '/about',
];

export function isPublicPath(path) {
    return PUBLIC_PATHS.includes(String(path ?? ''));
}
