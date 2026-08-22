import { describe, expect, it } from 'vitest';

import { LANDING_PAGES, PUBLIC_PATHS, isLandingPage, isPublicPath } from '@/Support/landing';

/**
 * Which pages wear the branding, and which links survive the lock.
 *
 * Two small lists that are easy to get wrong in opposite directions. Miss a
 * page off the landing list and a marketing page renders plain; add an app
 * page to it and somebody gets atmosphere over the form they are trying to
 * fill in. Miss a path off the public list while the site is locked and the
 * nav offers a link that bounces straight back to the lock screen.
 */
describe('isLandingPage', () => {
    it('dresses the marketing pages', () => {
        expect(isLandingPage('Home')).toBe(true);
        expect(isLandingPage('SnakesAndLadders')).toBe(true);
        expect(isLandingPage('OsrsClanEvents')).toBe(true);
        expect(isLandingPage('OsrsEventIdeas')).toBe(true);
    });

    /** About, Privacy, Terms and anything an admin adds later. */
    it('dresses every CMS page through the one component that renders them', () => {
        expect(isLandingPage('Page')).toBe(true);
    });

    /**
     * The whole point of the split. These are the screens people work in, and
     * decoration there is something to look past.
     */
    it('leaves the app plain', () => {
        for (const component of [
            'Boards/Index',
            'Boards/Mine',
            'BoardShow',
            'Events/Bingo',
            'Events/SkillRace',
            'Events/Participants',
            'Teams/Index',
            'Settings/Profile',
            'Settings/Account',
            'Admin/Boards',
            'Admin/Users',
            'SiteLock',
            'Auth/Login',
        ]) {
            expect(isLandingPage(component), component).toBe(false);
        }
    });

    it('is not fooled by an absent component name', () => {
        expect(isLandingPage(undefined)).toBe(false);
        expect(isLandingPage(null)).toBe(false);
        expect(isLandingPage('')).toBe(false);
    });

    /**
     * A near-miss is the realistic mistake: `Boards/Index` is not `Home`, and
     * a prefix match would have caught `PageBuilder` alongside `Page`.
     */
    it('matches the whole name, not a prefix of it', () => {
        expect(isLandingPage('HomeSettings')).toBe(false);
        expect(isLandingPage('Pages/Index')).toBe(false);
    });
});

describe('isPublicPath', () => {
    it('keeps the links that still go somewhere while the site is locked', () => {
        expect(isPublicPath('/')).toBe(true);
        expect(isPublicPath('/osrs-snakes-and-ladders')).toBe(true);
        expect(isPublicPath('/about')).toBe(true);
    });

    it('drops the ones that would bounce back to the lock screen', () => {
        for (const path of ['/events', '/my-events', '/teams', '/settings/profile', '/admin']) {
            expect(isPublicPath(path), path).toBe(false);
        }
    });

    it('handles nothing at all', () => {
        expect(isPublicPath(undefined)).toBe(false);
    });
});

describe('the two lists', () => {
    /**
     * They answer different questions — one is about decoration, one about
     * reachability — so they are deliberately not derived from each other.
     * What must hold is that neither is empty and neither has grown a
     * duplicate.
     */
    it('have no duplicates', () => {
        expect(LANDING_PAGES).toHaveLength(new Set(LANDING_PAGES).size);
        expect(PUBLIC_PATHS).toHaveLength(new Set(PUBLIC_PATHS).size);
    });

    it('every public path is an absolute one', () => {
        for (const path of PUBLIC_PATHS) {
            expect(path.startsWith('/'), path).toBe(true);
        }
    });
});
