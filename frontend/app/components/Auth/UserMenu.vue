<template>
  <div>
    <!-- ClientOnly prevents SSR hydration mismatch — skeleton shown on server via #fallback -->
    <client-only>
      <u-dropdown-menu v-if="authStore.isAuthenticated" :items="items">
        <u-button color="neutral" variant="ghost" class="gap-2">
          <u-avatar
            v-if="authStore.user?.avatarUrl"
            :src="authStore.user.avatarUrl"
            :alt="authStore.user.discordUsername"
            size="xs"
          />

          <u-icon v-else name="i-lucide-user" class="size-5" />

          <span class="hidden sm:inline text-sm">{{ authStore.displayName }}</span>

          <template v-if="authStore.user?.roles?.length">
            <u-badge
              v-for="role in authStore.user.roles"
              :key="role"
              :color="roleBadgeColor(role)"
              variant="subtle"
              size="xs"
              class="hidden sm:inline-flex"
            >
              {{ role }}
            </u-badge>
          </template>

          <u-icon name="i-lucide-chevron-down" class="size-4 text-muted" />
        </u-button>
      </u-dropdown-menu>

      <!-- Login button — only shown when not authenticated -->
      <u-button
        v-else
        color="primary"
        variant="solid"
        icon="i-simple-icons-discord"
        :loading="authStore.loading"
        @click="authStore.loginWithDiscord()"
      >
        {{ $t('common.login_discord') }}
      </u-button>

      <template #fallback>
        <u-skeleton class="h-8 w-28 rounded-full" />
      </template>
    </client-only>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

function roleBadgeColor(role: string): 'primary' | 'success' | 'warning' | 'error' | 'neutral' {
  const map: Record<string, 'primary' | 'success' | 'warning' | 'error' | 'neutral'> = {
    ADMIN: 'primary',
    EDITOR: 'success',
    TEAM_MANAGER: 'warning',
  };
  return map[role] ?? 'neutral';
}

const items = computed(() => [
  [
    {
      label: authStore.displayName ?? t('common.profile'),
      avatar: authStore.user?.avatarUrl ? { src: authStore.user.avatarUrl } : undefined,
      icon: authStore.user?.avatarUrl ? undefined : 'i-lucide-user',
      disabled: true,
    },
  ],
  [
    {
      label: t('common.profile'),
      icon: 'i-lucide-user-circle',
      to: '/profile',
    },
  ],
  [
    {
      label: t('common.logout'),
      icon: 'i-lucide-log-out',
      color: 'error' as const,
      onSelect: () => {
        authStore.logout();
        navigateTo('/');
      },
    },
  ],
]);
</script>
