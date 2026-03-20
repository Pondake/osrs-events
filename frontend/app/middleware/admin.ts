import { useAuthStore } from '~/stores/auth';

/**
 * Route middleware: redirect to home if not an ADMIN
 */
export default defineNuxtRouteMiddleware(() => {
  const authStore = useAuthStore();
  if (!authStore.isAdmin) {
    return navigateTo('/');
  }
});
