import { useAuthStore } from '~/stores/auth';

/**
 * Route middleware: redirect to home if not an ADMIN.
 * Works on both SSR and client — the universal auth plugin runs before any middleware
 * and populates the store from the request cookie on SSR.
 */
export default defineNuxtRouteMiddleware(() => {
  const authStore = useAuthStore();

  if (!authStore.isAdmin) {
    return navigateTo('/');
  }
});
