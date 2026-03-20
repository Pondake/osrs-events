import { useAuthStore } from '~/stores/auth';

/**
 * Route middleware: redirect to home if not authenticated
 */
export default defineNuxtRouteMiddleware(() => {
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    return navigateTo('/');
  }
});
