/**
 * The icons an admin may pick, grouped.
 *
 * **This file is imported by vite.config.js as well as by the app**, so it
 * must stay free of any import of its own — no i18n, no Vue. Group labels are
 * translation KEYS here; whoever renders them resolves them.
 *
 * Why a curated list rather than "search all of Iconify":
 * `clientBundle: { scan: true }` in vite.config.js bundles only the icons it
 * finds referenced in source. Anything else renders as a permanently empty
 * <svg> with no network request — the exact failure the Discord icon hit
 * before it was bundled explicitly. A picker offering every Iconify icon
 * would therefore let an admin choose icons that silently don't draw.
 *
 * So this list IS the contract: vite.config.js feeds it straight into
 * clientBundle.icons, which guarantees every pickable icon actually renders.
 * Adding an icon means adding it here and rebuilding — one place, and the
 * build is what makes it real.
 */
export const ICON_GROUPS = [
    {
        key: 'general',
        label: 'icons.group_general',
        icons: [
            'i-lucide-star', 'i-lucide-heart', 'i-lucide-flame', 'i-lucide-sparkles',
            'i-lucide-zap', 'i-lucide-award', 'i-lucide-trophy', 'i-lucide-medal',
            'i-lucide-crown', 'i-lucide-gem', 'i-lucide-gift', 'i-lucide-party-popper',
        ],
    },
    {
        key: 'game',
        label: 'icons.group_game',
        icons: [
            'i-lucide-dice-1', 'i-lucide-dice-2', 'i-lucide-dice-3', 'i-lucide-dice-4',
            'i-lucide-dice-5', 'i-lucide-dice-6', 'i-lucide-swords', 'i-lucide-sword',
            'i-lucide-shield', 'i-lucide-shield-check', 'i-lucide-target', 'i-lucide-map',
            'i-lucide-compass', 'i-lucide-pickaxe', 'i-lucide-anvil', 'i-lucide-castle',
            'i-lucide-grid-3x3', 'i-lucide-layout-grid', 'i-lucide-flag', 'i-lucide-skull',
        ],
    },
    {
        key: 'people',
        label: 'icons.group_people',
        icons: [
            'i-lucide-user', 'i-lucide-users', 'i-lucide-users-round', 'i-lucide-user-plus',
            // No crown here: it is already in `general`, and an icon listed
            // twice renders twice in the picker.
            'i-lucide-user-check', 'i-lucide-user-cog', 'i-lucide-handshake',
            'i-lucide-message-circle', 'i-lucide-message-square', 'i-lucide-mail', 'i-lucide-bell',
        ],
    },
    {
        key: 'actions',
        label: 'icons.group_actions',
        icons: [
            'i-lucide-arrow-right', 'i-lucide-arrow-up-right', 'i-lucide-external-link',
            'i-lucide-download', 'i-lucide-upload', 'i-lucide-play', 'i-lucide-plus',
            'i-lucide-check', 'i-lucide-check-square', 'i-lucide-list-checks',
            'i-lucide-search', 'i-lucide-settings', 'i-lucide-pencil', 'i-lucide-trash-2',
        ],
    },
    {
        key: 'status',
        label: 'icons.group_status',
        icons: [
            'i-lucide-info', 'i-lucide-circle-check', 'i-lucide-circle-x',
            'i-lucide-triangle-alert', 'i-lucide-circle-alert', 'i-lucide-alert-triangle',
            'i-lucide-clock', 'i-lucide-calendar', 'i-lucide-lock', 'i-lucide-unlock',
            'i-lucide-eye', 'i-lucide-eye-off',
        ],
    },
    {
        key: 'content',
        label: 'icons.group_content',
        icons: [
            'i-lucide-text', 'i-lucide-file-text', 'i-lucide-book-open', 'i-lucide-newspaper',
            'i-lucide-image', 'i-lucide-video', 'i-lucide-link', 'i-lucide-quote',
            'i-lucide-rows-3', 'i-lucide-panel-top', 'i-lucide-megaphone', 'i-lucide-minus',
            'i-lucide-coffee', 'i-lucide-ticket', 'i-lucide-scroll-text', 'i-lucide-moon',
        ],
    },
];

/** Flat list, de-duplicated — a few icons sit in more than one group. */
export const ICON_NAMES = [...new Set(ICON_GROUPS.flatMap((group) => group.icons))];
