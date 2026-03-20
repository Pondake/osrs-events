import { useAuthStore } from '~/stores/auth';

/**
 * Client-side plugin: load auth token from localStorage and fetch current user
 */
export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore();
  authStore.loadFromStorage();

  if (authStore.token) {
    await authStore.fetchMe();
  }

  // Signal to the UI that auth state is now ready — prevents the "Login" flash on load
  authStore.hydrated = true;
});
