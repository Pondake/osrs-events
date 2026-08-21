<template>
    <u-dropdown-menu v-if="isAuthenticated" :items="items">
        <u-button color="neutral" variant="ghost" class="gap-2">
            <u-avatar v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.discordUsername" size="xs" />
            <u-icon v-else name="i-lucide-user" class="size-5" />
            <span class="hidden sm:inline text-sm">{{ user.nickname ?? user.discordUsername }}</span>
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

const items = computed(() => [
    [{ label: user.value?.nickname ?? user.value?.discordUsername, disabled: true }],
    [
        { label: trans('settings.nav_profile'), icon: 'i-lucide-user-circle', to: '/settings/profile' },
        { label: trans('settings.nav_account'), icon: 'i-lucide-shield', to: '/settings/account' },
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
