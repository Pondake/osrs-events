<template>
  <u-main>
    <u-page>
    <u-page-hero
      :title="$t('home.title')"
      :description="$t('home.description')"
      :links="
        authStore.isAuthenticated
          ? [
              {
                label: $t('home.cta_boards'),
                to: '/boards',
                trailingIcon: 'i-lucide-arrow-right',
                size: 'xl' as const,
              },
            ]
          : [
              {
                label: $t('home.cta_login'),
                icon: 'i-simple-icons-discord',
                size: 'xl' as const,
                onClick: () => authStore.loginWithDiscord(),
              },
            ]
      "
    />

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
      ]"
    />
    </u-page>
  </u-main>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
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
    // Clean the URL without a full page reload
    navigateTo('/', { replace: true });
  }
});
</script>
