import { eventId } from './db.js';

/** The page types the layout and colour passes read, by the seat that sees them. */
export const EVENT_TITLES = [
    'E2E Ladder',
    'The Grand Midsummer Clan Championship of Old School RuneScape — Season Four',
    'Ended last week',
    'Starts next month',
    'On hold',
    'Zulrah sprint',
    'Invite only night',
    'Teams of four',
    'Photo finish',
];

export const PAGES = {
    anonymous: () => [
        '/',
        '/osrs-snakes-and-ladders',
        '/osrs-clan-events',
        '/osrs-event-ideas',
        '/osrs-bingo',
        '/osrs-skill-race',
        '/osrs-drop-race',
        '/about',
        '/beta',
        '/privacy',
        '/terms',
        '/events',
        '/events/all',
        '/login',
        '/register',
        '/forgot-password',
        ...EVENT_TITLES.map((title) => `/events/${eventId(title)}`),
        `/events/${eventId('E2E Ladder')}/participants`,
    ],
    member: () => [
        '/my-events',
        '/community',
        '/teams',
        '/settings/profile',
        '/settings/account',
        '/settings/connections',
        '/settings/notifications',
        '/settings/animations',
    ],
    admin: () => [
        '/admin',
        '/admin/users',
        '/admin/events',
        '/admin/tasks',
        '/admin/boss-icons',
        '/admin/blueprints',
        '/admin/site',
        '/admin/content',
        '/admin/content/privacy',
        '/admin/invites',
        '/admin/audit',
        '/admin/diagnostics',
    ],
};

/** The three numbers of the pass, plus the width the browser really has. */
export function measure() {
    const root = document.documentElement;
    const wide = [...document.querySelectorAll('body *')]
        .filter((element) => element.getBoundingClientRect().right > root.clientWidth + 1)
        .slice(0, 3)
        .map((element) => `${element.tagName.toLowerCase()}.${String(element.className).split(' ')[0]}`.slice(0, 50));
    const small = [...document.querySelectorAll('button, a[href]')].filter((element) => {
        const box = element.getBoundingClientRect();

        return box.width > 0 && box.height > 0 && box.height < 44;
    }).length;

    return { dark: root.classList.contains('dark'), width: root.clientWidth, overflow: root.scrollWidth - root.clientWidth, wide, under44: small };
}
