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
    'OsrsBingo',
    'OsrsSkillRace',
    'OsrsDropRace',
    // Every CMS page — privacy, terms, donate. They are read by the same
    // people, in the same frame of mind, and they are what a search result
    // lands on.
    'Page',
    // /about was one of those until 2026-09-07 and is its own component now,
    // so it needs naming here in its own right. It leans on the panel bevel
    // more than any of them — see About.vue.
    'About',
    // The error page, for the same reason: a dead link from a search result
    // is somebody's first sight of the site, and the panel treatment is what
    // makes it read as a page rather than as a crash. It also gets the panel
    // bevel for free — see .landing-page in app.css.
    'Error',
];

export function isLandingPage(component) {
    return LANDING_PAGES.includes(String(component ?? ''));
}

/**
 * Which pattern the generative background draws behind a landing page.
 *
 * Three motifs, all of them the app's own language rather than a borrowed
 * one: the square the game world is divided into, the bingo card's diamond
 * lattice, and a Snakes & Ladders board. The page that sells an event type
 * gets the shape of that event; everything else gets the plain tile, which is
 * the unit all three are made of.
 *
 * Only landing pages are asked — the caller gates on isLandingPage() first.
 * That gate is the whole point: this belongs on the pages people read, not
 * behind a bingo card somebody is playing or a scoreboard that is moving.
 */
const MOTIFS = {
    OsrsBingo: 'bingo',
    SnakesAndLadders: 'ladder',
};

export function backgroundMotif(component) {
    return MOTIFS[String(component ?? '')] ?? 'tiles';
}

/**
 * The landing pages that take the pattern at half strength.
 *
 * Everything else on this list opens on a hero — a heading, a line of copy
 * and a couple of buttons over open space — and carries the field at full
 * strength happily. `Page` is the CMS one: privacy, terms, donate. Those are
 * a wall of text from the first line down, and the same strength behind a
 * paragraph somebody is actually reading is clutter rather than texture.
 *
 * The same split collectopoly makes on `route.meta.layout`, decided here on
 * the component name because that is what the shell knows about the page.
 */
const QUIET_PAGES = ['Page'];

export function backgroundIsHero(component) {
    return ! QUIET_PAGES.includes(String(component ?? ''));
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
    '/osrs-bingo',
    '/osrs-skill-race',
    '/osrs-drop-race',
    '/about',
];

export function isPublicPath(path) {
    return PUBLIC_PATHS.includes(String(path ?? ''));
}
