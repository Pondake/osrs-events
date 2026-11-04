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
                <span class="text-sm leading-none">{{ displayName }}</span>
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
    <!-- `:active="false"` is not styling — it short-circuits Nuxt UI's
         Inertia ULink before it reads `usePage().url`, which is undefined on
         this component's first render because the header sits before the page
         component. See AppHeader's isCurrent() for the full account. A login
         button has no active state to lose: the login page does not show it. -->
    <u-button v-else to="/login" :active="false" color="primary" variant="solid" icon="i-lucide-log-in" :label="$t('common.login')" />
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useUserMenu } from '@/Composables/useUserMenu';

const { user, isAuthenticated, displayName, primaryRole, roleClass, linkGroups, logout } = useUserMenu();

const items = computed(() => [
    [{ label: displayName.value, disabled: true }],
    ...linkGroups.value,
    [{ label: trans('common.logout'), icon: 'i-lucide-log-out', color: 'error', onSelect: logout }],
]);
</script>
