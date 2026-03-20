<template>
  <nuxt-layout>
    <u-page-body>
      <div class="flex flex-col items-center justify-center min-h-64 gap-4">
        <u-icon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin" />

        <p class="text-muted">{{ $t('auth.logging_in') }}</p>
      </div>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const route = useRoute();
const toast = useToast();

onMounted(async () => {
  const token = route.query.token as string;
  const error = route.query.error as string;

  if (error) {
    toast.add({
      title: t('auth.login_failed_title'),
      description: t('auth.login_failed_desc'),
      color: 'error',
    });
    navigateTo('/');
    return;
  }

  if (token) {
    authStore.setToken(token);
    await authStore.fetchMe();

    toast.add({
      title: t('auth.login_success_title'),
      description: t('auth.login_success_desc', { name: authStore.user?.discordUsername ?? '' }),
      color: 'success',
    });

    // Replace history entry so the JWT token doesn't remain in browser history
    navigateTo('/boards', { replace: true });
  } else {
    navigateTo('/');
  }
});
</script>
