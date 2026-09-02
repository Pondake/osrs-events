/**
 * The account settings destinations, in one place.
 *
 * Two menus point at these — the settings sidebar and the header's user menu —
 * and they had their own copies. The copies drifted: Connections and
 * Animations existed in the sidebar and were simply missing from the header,
 * so a setting could be added and stay unreachable from the place most people
 * open first. Reported twice.
 *
 * A function rather than a constant because the labels are translated: called
 * at module load, `trans()` would resolve before the locale is installed.
 */
import { trans } from 'laravel-vue-i18n';

/** @returns {Array<{key: string, to: string, icon: string, label: string}>} */
export function settingsItems() {
    return [
        { key: 'profile', to: '/settings/profile', icon: 'i-lucide-user-circle', label: trans('settings.nav_profile') },
        { key: 'account', to: '/settings/account', icon: 'i-lucide-shield', label: trans('settings.nav_account') },
        // Split off Account 2026-08-30: which outside services this account
        // talks to is a different question from how it is signed into, and one
        // page was answering both.
        { key: 'connections', to: '/settings/connections', icon: 'i-lucide-plug', label: trans('settings.nav_connections') },
        { key: 'notifications', to: '/settings/notifications', icon: 'i-lucide-bell', label: trans('settings.nav_notifications') },
        // Its own item rather than a card on the profile: a profile is who you
        // are, this is how the game behaves while you watch it.
        { key: 'animations', to: '/settings/animations', icon: 'i-lucide-play', label: trans('settings.nav_animations') },
    ];
}

/**
 * The same list, grouped the way the settings sidebar prints it.
 *
 * The grouping is the sidebar's own concern — the header menu wants a flat
 * list with its own name header and admin/logout rows around it — so the
 * shared thing is the items, and each menu decides how to wrap them.
 */
export function settingsGroups() {
    return [
        { key: 'account', label: trans('settings.group_account'), items: settingsItems() },
    ];
}
