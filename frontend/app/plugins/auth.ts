import { useAuthStore } from '~/stores/auth';

/**
 * Universal plugin: runs on both SSR and client.
 *
 * On SSR  — reads the JWT from the request cookie, sets it on the store, fetches /auth/me.
 *           The resulting user + token state is serialized and sent to the client.
 * On client — Pinia state is already hydrated from SSR (user + token already set).
 *             We skip fetchMe if user is already present, and migrate any legacy
 *             localStorage token to a cookie for future SSR requests.
 */
export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore();

  if (import.meta.server) {
    // Read the JWT from the incoming request cookie
    const tokenCookie = useCookie<string | null>('auth_token');
    if (tokenCookie.value) {
      authStore.token = tokenCookie.value;
    }
  } else {
    // Client: migrate localStorage token to cookie (one-time migration for existing users)
    authStore.loadFromStorage();
  }

  // Fetch user if we have a token but no user yet.
  // On the client after SSR, user is already populated from the transferred Pinia state.
  if (authStore.token && !authStore.user) {
    await authStore.fetchMe();
  }

  authStore.hydrated = true;
});
