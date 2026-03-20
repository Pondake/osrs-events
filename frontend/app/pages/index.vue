<template>
  <u-main>
    <u-page>
      <!-- ── Hero ─────────────────────────────────────────────────── -->
      <u-page-hero :title="$t('home.title')" :description="$t('home.description')">
        <template #links>
          <!-- Skeleton while auth hydrates -->
          <u-skeleton v-if="!authStore.hydrated" class="h-11 w-44 rounded-lg" />

          <u-button
            v-else-if="authStore.isAuthenticated"
            to="/boards"
            trailing-icon="i-lucide-arrow-right"
            size="xl"
            color="primary"
          >
            {{ $t('home.cta_boards') }}
          </u-button>

          <u-button
            v-else
            size="xl"
            icon="i-simple-icons-discord"
            color="primary"
            @click="authStore.loginWithDiscord()"
          >
            {{ $t('home.cta_login') }}
          </u-button>
        </template>
      </u-page-hero>

      <!-- ── How it works ──────────────────────────────────────────── -->
      <u-page-section
        :title="$t('home.how_it_works')"
        :description="$t('home.how_subtitle')"
        :features="[
          {
            icon: 'i-simple-icons-discord',
            title: $t('home.feature_discord_title'),
            description: $t('home.feature_discord_desc'),
          },
          {
            icon: 'i-lucide-layout-grid',
            title: $t('home.feature_boards_title'),
            description: $t('home.feature_boards_desc'),
          },
          {
            icon: 'i-lucide-dice-6',
            title: $t('home.feature_dice_title'),
            description: $t('home.feature_dice_desc'),
          },
          {
            icon: 'i-lucide-list-checks',
            title: $t('home.feature_tasks_title'),
            description: $t('home.feature_tasks_desc'),
          },
          {
            icon: 'i-lucide-arrow-up-from-line',
            title: $t('home.feature_snakes_title'),
            description: $t('home.feature_snakes_desc'),
          },
          {
            icon: 'i-lucide-user-round',
            title: $t('home.feature_profile_title'),
            description: $t('home.feature_profile_desc'),
          },
        ]"
      />

      <!-- ── Admin section (admins/editors only) ───────────────────── -->
      <u-page-section
        v-if="authStore.hydrated && authStore.isAdmin"
        :title="$t('home.admin_title')"
        :description="$t('home.admin_subtitle')"
        :links="[
          {
            label: $t('nav.admin_boards'),
            to: '/admin/boards',
            icon: 'i-lucide-settings',
            color: 'primary',
            variant: 'outline',
          },
          {
            label: $t('nav.tasks'),
            to: '/admin/tasks',
            icon: 'i-lucide-list-checks',
            color: 'neutral',
            variant: 'outline',
          },
        ]"
      />
    </u-page>
  </u-main>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const authStore = useAuthStore();
const { t } = useI18n();
const route = useRoute();
const toast = useToast();

// Handle error redirected from backend (e.g. Discord OAuth failed)
onMounted(() => {
  if (route.query.error === 'auth_failed') {
    toast.add({
      title: t('auth.login_failed_title'),
      description: t('auth.login_failed_desc'),
      color: 'error',
    });
    navigateTo('/', { replace: true });
  }
});
</script>
