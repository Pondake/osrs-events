<template>
  <u-header
    mode="drawer"
    :toggle="navigation.length > 0"
    :ui="{ content: 'top-[var(--ui-header-height)]' }"
    :menu="{
      direction: 'top',
      inset: true,
      shouldScaleBackground: true,
    }"
  >
    <!-- u-header already wraps this slot in its own link to `/`. Adding a
         nuxt-link here would nest <a> inside <a>, which the HTML parser hoists
         apart — producing a hydration mismatch on every page. -->
    <template #title>
      <span class="osrs-title text-lg font-bold text-highlighted">⚔️ OSRS Events</span>
    </template>

    <!-- Rendered on the server too: auth state is resolved during SSR and
         transferred via the Pinia payload, so `navigation` is identical on the
         first client render. -->
    <u-navigation-menu v-if="navigation.length" :items="navigation" />

    <template #right>
      <u-color-mode-button />

      <auth-user-menu />
    </template>

    <template v-if="navigation.length > 0" #body>
      <div class="p-4">
        <u-navigation-menu :items="navigation" orientation="vertical" />
      </div>
    </template>
  </u-header>
</template>

<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui';

import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const navigation = computed<NavigationMenuItem[]>(() => {
  const items: NavigationMenuItem[] = [];
  const isAdmin = authStore.isAdmin;
  const isEditor = authStore.user?.roles?.includes('EDITOR') ?? false;
  const isTeamManager = authStore.user?.roles?.includes('TEAM_MANAGER') ?? false;
  const canManageBoards = isAdmin || isEditor;

  if (!authStore.isAuthenticated) return [];

  // ── Boards ──────────────────────────────────────────────────────────
  if (canManageBoards) {
    items.push({
      label: t('nav.boards'),
      icon: 'i-lucide-layout-grid',
      children: [
        {
          label: t('nav.boards'),
          to: '/boards',
          icon: 'i-lucide-layout-grid',
          description: t('nav.boards_desc'),
        },
        {
          label: t('nav.admin_boards'),
          to: '/admin/boards',
          icon: 'i-lucide-settings',
          description: t('nav.admin_boards_desc'),
        },
      ],
    });
  } else {
    items.push({ label: t('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid' });
  }

  // ── Teams ───────────────────────────────────────────────────────────
  if (isAdmin || isTeamManager) {
    items.push({ label: t('nav.teams'), to: '/teams', icon: 'i-lucide-users' });
  }

  // ── Tasks ───────────────────────────────────────────────────────────
  if (isAdmin || isEditor) {
    items.push({ label: t('nav.tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks' });
  }

  // ── Admin ───────────────────────────────────────────────────────────
  if (isAdmin) {
    items.push({
      label: t('nav.admin'),
      icon: 'i-lucide-shield',
      children: [
        {
          label: t('nav.admin_users'),
          to: '/admin/users',
          icon: 'i-lucide-user-cog',
          description: t('nav.admin_users_desc'),
        },
        {
          label: t('nav.admin_teams'),
          to: '/admin/teams',
          icon: 'i-lucide-users',
          description: t('nav.admin_teams_desc'),
        },
      ],
    });
  }

  return items;
});
</script>
