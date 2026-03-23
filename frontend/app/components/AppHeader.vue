<template>
  <u-header
    mode="drawer"
    :toggle="navigation.length > 0"
    :ui="{ content: 'top-[var(--ui-header-height)]' }"
    :menu="{
      direction: 'top',
      inset: true,
      shouldScaleBackground: true,
      ui: { body: 'bg-red-200', overlay: ' bg-red-200 top-[var(--ui-header-height)]' },
    }"
  >
    <template #title>
      <nuxt-link to="/" class="flex items-center gap-2">
        <span class="osrs-title text-lg font-bold text-highlighted">⚔️ OSRS Events</span>
      </nuxt-link>
    </template>

    <!-- Desktop navigation — client-only to avoid SSR hydration mismatch -->
    <client-only>
      <template #fallback>
        <div class="hidden md:flex gap-3 items-center">
          <u-skeleton class="h-5 w-14 rounded" />
          <u-skeleton class="h-5 w-24 rounded" />
        </div>
      </template>

      <u-navigation-menu v-if="navigation.length" :items="navigation" />
    </client-only>

    <template #right>
      <u-color-mode-button />
      <auth-user-menu />
    </template>

    <template v-if="authStore.hydrated && navigation.length > 0" #body>
      <div class="p-4">
        <u-navigation-menu :items="navigation" orientation="vertical" />
      </div>
    </template>
  </u-header>
</template>

<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import { useAuthStore } from '~/stores/auth'

const { t } = useI18n()
const authStore = useAuthStore()

const navigation = computed<NavigationMenuItem[]>(() => {
  if (!authStore.hydrated) return []

  const items: NavigationMenuItem[] = []
  const isAdmin = authStore.isAdmin
  const isEditor = authStore.user?.roles?.includes('EDITOR') ?? false
  const isTeamManager = authStore.user?.roles?.includes('TEAM_MANAGER') ?? false
  const canManageBoards = isAdmin || isEditor

  if (!authStore.isAuthenticated) return []

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
    })
  } else {
    items.push({ label: t('nav.boards'), to: '/boards', icon: 'i-lucide-layout-grid' })
  }

  // ── Teams ───────────────────────────────────────────────────────────
  if (isAdmin || isTeamManager) {
    items.push({ label: t('nav.teams'), to: '/teams', icon: 'i-lucide-users' })
  }

  // ── Tasks ───────────────────────────────────────────────────────────
  if (isAdmin || isEditor) {
    items.push({ label: t('nav.tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks' })
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
    })
  }

  return items
})
</script>
