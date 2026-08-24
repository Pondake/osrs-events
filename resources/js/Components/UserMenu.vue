<template>
    <u-dropdown-menu v-if="isAuthenticated" :items="items">
        <u-button color="neutral" variant="ghost" class="gap-2">
            <u-avatar v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.discordUsername" size="xs" />
            <u-icon v-else name="i-lucide-user" class="size-5" />

            <!-- Name over role, stacked. The header had room under the name
                 and nothing in it, and the role is the one thing about your
                 account that changes what the rest of the site shows you —
                 worth reading at a glance rather than only from the profile
                 page. `leading-none` on both lines: two default line-heights
                 stacked would push the button taller than the rest of the
                 header row. -->
            <span class="hidden sm:flex flex-col items-start leading-none gap-0.5">
                <span class="text-sm leading-none">{{ user.nickname ?? user.discordUsername }}</span>
                <span v-if="primaryRole" class="text-[10px] font-medium uppercase tracking-wide leading-none" :class="roleClass">
                    {{ primaryRole }}
                </span>
            </span>

            <u-icon name="i-lucide-chevron-down" class="size-4 text-muted" />
        </u-button>
    </u-dropdown-menu>

    <!-- One button, one destination. This was a dropdown offering Discord,
         log in and create account side by side — three doors to the same
         room, in a header that has no space to explain the difference, and
         the Discord one skipped the login page entirely. The login page
         already presents every method with room to label them. -->
    <u-button v-else to="/login" color="primary" variant="solid" icon="i-lucide-log-in" :label="$t('common.login')" />
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';

const { user, isAuthenticated, isAdmin, canCreateTiles } = useAuth();

// Same gate as the /admin middleware — an EDITOR with canCreateTiles gets
// in for the Tasks page alone. The link only mirrors that; the server is
// what actually enforces it.
const canReachAdmin = computed(() => isAdmin.value || canCreateTiles.value);

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

const primaryRole = computed(() => {
    const roles = user.value?.roles ?? [];

    return ROLE_RANK.find((role) => roles.includes(role)) ?? null;
});

const roleClass = computed(() => ROLE_CLASS[primaryRole.value] ?? 'text-muted');

const items = computed(() => [
    [{ label: user.value?.nickname ?? user.value?.discordUsername, disabled: true }],
    [
        { label: trans('settings.nav_profile'), icon: 'i-lucide-user-circle', to: '/settings/profile' },
        { label: trans('settings.nav_account'), icon: 'i-lucide-shield', to: '/settings/account' },
        { label: trans('settings.nav_notifications'), icon: 'i-lucide-bell', to: '/settings/notifications' },
    ],
    ...(canReachAdmin.value
        ? [[{ label: trans('admin.nav_admin_area'), icon: 'i-lucide-layout-dashboard', to: '/admin' }]]
        : []),
    [{ label: trans('common.logout'), icon: 'i-lucide-log-out', color: 'error', onSelect: logout }],
]);

// router.post(), not a raw <form> submit — Inertia's client handles CSRF
// itself via the XSRF-TOKEN cookie for its own fetch-based requests (same
// mechanism BoardSettingsModal's invite fetch() calls use explicitly). A
// plain form submission would need the SESSION _token instead (a different
// value from the XSRF-TOKEN cookie), which isn't exposed anywhere in this
// app (no <meta name="csrf-token">) — reaching for that would have been a
// second, wrong fix on top of the barrel-import CSRF mixup already caught
// once in this session.
function logout() {
    router.post('/logout');
}
</script>
