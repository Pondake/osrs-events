/**
 * The six OSRS Events guide pages, in one place.
 *
 * Two surfaces show this same list — the header's Guides dropdown
 * (AppHeader.vue) and every guide page's own "Other guides" sidebar link
 * (GuideLayout.vue) — and it used to be typed out twice, which is how the
 * dropdown and the sidebar would eventually disagree about what a "guide"
 * even is. `labelKey` rather than a resolved label: callers pass it through
 * `trans()`/`$t()` themselves, so this file carries no i18n dependency of
 * its own.
 */
/**
 * Tailwind classes for a guide page's article body — same handful of
 * heading/paragraph/list/FAQ styles needed on all six guide pages. A scoped
 * `<style>` with `@apply` was tried first and dropped: Tailwind v4 rejects
 * `@apply` inside a Vue SFC's scoped style block unless it also imports the
 * theme via `@reference`, which isn't a pattern used anywhere else in this
 * codebase — plain utility classes on the tags themselves, shared from here
 * so the six pages don't each retype the same class strings, is the smaller
 * amount of new machinery.
 */
export const GUIDE_PROSE = {
    // No `first:mt-0`: each <h2> lives inside its own <section>, so it is
    // already the first child there — a `first:` variant would zero out
    // mt-12 on every section's heading, not just the page's opening one.
    // (Exactly what shipped here initially, and why the first release of
    // this layout had every heading hugging the paragraph above it.)
    h2: 'text-2xl font-bold text-highlighted mt-12 mb-4 pb-2 border-b border-default',
    h3: 'text-lg font-semibold text-highlighted mt-8 mb-2',
    p: 'text-muted leading-relaxed mb-4',
    list: 'mb-4 space-y-1',
    faqRow: 'py-5 border-b border-default first:pt-0 last:border-b-0',
    faqQuestion: 'font-semibold text-highlighted',
    faqAnswer: 'mt-1.5 text-muted leading-relaxed',
};

export const GUIDE_LINKS = [
    { to: '/osrs-snakes-and-ladders', labelKey: 'nav.snakes', icon: 'i-lucide-arrow-up-from-line' },
    { to: '/osrs-bingo', labelKey: 'nav.bingo', icon: 'i-lucide-grid-3x3' },
    { to: '/osrs-skill-race', labelKey: 'nav.skill_race', icon: 'i-lucide-trophy' },
    { to: '/osrs-drop-race', labelKey: 'nav.drop_race', icon: 'i-lucide-swords' },
    { to: '/osrs-clan-events', labelKey: 'nav.clan_events', icon: 'i-lucide-users' },
    { to: '/osrs-event-ideas', labelKey: 'nav.event_ideas', icon: 'i-lucide-lightbulb' },
];
