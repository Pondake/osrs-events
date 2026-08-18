<template>
    <u-dropdown-menu v-if="isAuthenticated" :items="items">
        <u-button color="neutral" variant="ghost" class="gap-2">
            <u-avatar v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.discordUsername" size="xs" />
            <u-icon v-else name="i-lucide-user" class="size-5" />
            <span class="hidden sm:inline text-sm">{{ user.nickname ?? user.discordUsername }}</span>
            <u-icon name="i-lucide-chevron-down" class="size-4 text-muted" />
        </u-button>
    </u-dropdown-menu>

    <u-button v-else :href="loginHref" color="primary" variant="solid" icon="i-simple-icons-discord">
        <span class="sm:hidden">Login</span>
        <span class="hidden sm:inline">Login with Discord</span>
    </u-button>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';

const { user, isAuthenticated } = useAuth();

const items = computed(() => [
    [{ label: user.value?.nickname ?? user.value?.discordUsername, disabled: true }],
    [{ label: 'Profile', icon: 'i-lucide-user-circle', to: '/profile' }],
    [{ label: 'Logout', icon: 'i-lucide-log-out', color: 'error', onSelect: logout }],
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

const loginHref = '/auth/discord/redirect';
</script>
