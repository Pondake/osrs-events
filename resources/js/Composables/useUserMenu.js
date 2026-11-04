import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import { settingsItems } from '@/Support/settingsNav';

/**
 * One badge, not all of them. An account can hold several roles and the
 * header has room for one line — so this shows the most privileged, which is
 * the one that explains what you can see. Ordered most-to-least; the first
 * match wins.
 *
 * PLAYER is deliberately absent: it is what everybody has, so printing it
 * under every name is a row of noise that distinguishes nobody. The full
 * set still shows on /settings/profile.
 */
const ROLE_RANK = ['ADMIN', 'EDITOR'];

const ROLE_CLASS = {
    ADMIN: 'text-error',
    EDITOR: 'text-warning',
};

/**
 * The account menu's contents, shared by the desktop dropdown (UserMenu) and
 * the account section of the mobile drawer (AppHeader), so the two cannot
 * offer different things.
 */
export function useUserMenu() {
    const { user, isAuthenticated, isAdmin, canCreateTiles } = useAuth();

    // Same gate as the /admin middleware — an EDITOR with canCreateTiles gets
    // in for the Tasks page alone. The link only mirrors that; the server is
    // what actually enforces it.
    const canReachAdmin = computed(() => isAdmin.value || canCreateTiles.value);

    const displayName = computed(() => user.value?.nickname ?? user.value?.discordUsername);

    const primaryRole = computed(() => {
        const roles = user.value?.roles ?? [];

        return ROLE_RANK.find((role) => roles.includes(role)) ?? null;
    });

    const roleClass = computed(() => ROLE_CLASS[primaryRole.value] ?? 'text-muted');

    /**
     * The settings rows come from the same list the settings sidebar prints.
     *
     * They used to be typed out again here, and fell behind twice — Connections
     * and then Animations existed in the sidebar and were missing from this
     * menu, which is the one most people open first.
     */
    const linkGroups = computed(() => [
        settingsItems().map(({ label, icon, to }) => ({ label, icon, to })),
        ...(canReachAdmin.value
            ? [[{ label: trans('admin.nav_admin_area'), icon: 'i-lucide-layout-dashboard', to: '/admin' }]]
            : []),
    ]);

    // router.post(), not a raw <form> submit — Inertia's client handles CSRF
    // itself via the XSRF-TOKEN cookie for its own fetch-based requests. A
    // plain form submission would need the SESSION _token instead, which isn't
    // exposed anywhere in this app (no <meta name="csrf-token">).
    function logout() {
        router.post('/logout');
    }

    return { user, isAuthenticated, displayName, primaryRole, roleClass, linkGroups, logout };
}
