<template>
  <u-header>
    <template #left>
      <nuxt-link to="/" class="flex items-center gap-2">
        <span class="osrs-title text-lg font-bold text-primary">⚔️ OSRS Events</span>
      </nuxt-link>
    </template>

    <!-- Desktop navigation — client-only to avoid SSR hydration mismatch -->
    <client-only>
      <!-- Skeleton while auth hydrates -->
      <template #fallback>
        <div class="hidden md:flex gap-3 items-center">
          <u-skeleton class="h-5 w-14 rounded" />
          <u-skeleton class="h-5 w-24 rounded" />
        </div>
      </template>

      <!-- Skeleton while auth hydrates on client -->
      <div v-if="!authStore.hydrated" class="hidden md:flex gap-3 items-center">
        <u-skeleton class="h-5 w-14 rounded" />
        <u-skeleton class="h-5 w-24 rounded" />
      </div>

      <u-navigation-menu v-else-if="navigation.length" :items="navigation" />
    </client-only>

    <template #right>
      <u-color-mode-button />

      <auth-user-menu />
    </template>

    <!-- Mobile panel — UHeader renders the toggle button automatically -->
    <template #panel>
      <client-only>
        <div v-if="!authStore.hydrated" class="p-4 flex flex-col gap-2">
          <u-skeleton class="h-8 w-full rounded" />
          <u-skeleton class="h-8 w-full rounded" />
        </div>

        <div v-else-if="navigation.length" class="p-4">
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
