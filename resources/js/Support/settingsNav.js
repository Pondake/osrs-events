/**
 * The account settings destinations. Shared by the settings sidebar and the
 * header's user menu, which each used to keep their own copy and drift apart.
 *
 * A function, not a constant: `trans()` at module load runs before the locale
 * is installed.
 */
import { trans } from 'laravel-vue-i18n';

/** @returns {Array<{key: string, to: string, icon: string, label: string}>} */
export function settingsItems() {
    return [
        { key: 'profile', to: '/settings/profile', icon: 'i-lucide-user-circle', label: trans('settings.nav_profile') },
        { key: 'account', to: '/settings/account', icon: 'i-lucide-shield', label: trans('settings.nav_account') },
        { key: 'connections', to: '/settings/connections', icon: 'i-lucide-plug', label: trans('settings.nav_connections') },
        { key: 'notifications', to: '/settings/notifications', icon: 'i-lucide-bell', label: trans('settings.nav_notifications') },
        { key: 'animations', to: '/settings/animations', icon: 'i-lucide-play', label: trans('settings.nav_animations') },
    ];
}

/** The same list, grouped the way the sidebar prints it. */
export function settingsGroups() {
    return [
        { key: 'account', label: trans('settings.group_account'), items: settingsItems() },
    ];
}
