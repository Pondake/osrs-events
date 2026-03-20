<template>
  <u-header>
    <template #left>
      <nuxt-link to="/" class="flex items-center gap-2">
        <span class="osrs-title text-lg font-bold text-primary">🐍 OSRS S&amp;L</span>
      </nuxt-link>
    </template>

    <!-- Desktop navigation — client-only to avoid SSR hydration mismatch -->
    <client-only>
      <u-navigation-menu v-if="navigation.length" :items="navigation" />
    </client-only>

    <template #right>
      <u-color-mode-button />

      <auth-user-menu />
    </template>

    <!-- Mobile panel — UHeader renders the toggle button automatically -->
    <template #panel>
      <client-only>
        <div v-if="navigation.length" class="p-4">
          <u-navigation-menu :items="navigation" orientation="vertical" />
        </div>
      </client-only>
    </template>
  </u-header>
</template>

<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui';

import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const navigation = computed<NavigationMenuItem[]>(() => {
  // Only populate once client-side auth state is known
  if (!authStore.hydrated) return [];

  const items: NavigationMenuItem[] = [];

  if (authStore.isAuthenticated) {
    items.push({ label: t('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid' });
  }

  if (authStore.isAdmin) {
    items.push(
      { label: t('nav.admin_boards'), to: '/admin/boards', icon: 'i-lucide-settings' },
      { label: t('nav.tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks' },
    );
  }

  return items;
});
</script>
