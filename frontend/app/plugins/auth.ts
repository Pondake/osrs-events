import { useAuthStore } from '~/stores/auth';

/**
 * Universal plugin: runs on both SSR and client.
 *
 * On SSR  — reads the JWT from the request cookie, sets it on the store, fetches
 *           /auth/me and marks the store hydrated. All of that is serialized
 *           into the Nuxt payload.
 *
 * On client — Pinia has already restored that payload, so the store matches the
 *           server exactly and the first client render reproduces the server
 *           HTML. Anything that could *change* auth state is therefore deferred
 *           until after hydration (see below) rather than run in the plugin
 *           body, which executes before the app mounts.
 */
export default defineNuxtPlugin(async nuxtApp => {
  const authStore = useAuthStore();

  if (import.meta.server) {
    // Read the JWT from the incoming request cookie
    const tokenCookie = useCookie<string | null>('auth_token');
    if (tokenCookie.value) {
      authStore.token = tokenCookie.value;
    }

    if (authStore.token && !authStore.user) {
      // Awaited so the rendered HTML already reflects the logged-in state.
      await authStore.fetchMe();
    }

    authStore.hydrated = true;
    return;
  }

  // ── Client ────────────────────────────────────────────────────────────
  // Legacy users who logged in before cookie auth existed still only have a
  // token in localStorage, which SSR cannot see — so the server renders them
  // logged out. Restoring that token here in the plugin body would flip the
  // store to logged-in *before* the app mounts, so Vue's first client render
  // would disagree with the server HTML: a hydration mismatch on every
  // auth-dependent branch (header nav, user menu, home CTA).
  //
  // Deferring to app:suspense:resolve — which fires once hydration is complete
  // — turns that into an ordinary post-hydration re-render instead. Writing the
  // cookie also means the *next* SSR request renders them logged in directly,
  // so the fallback costs one render, once.
  nuxtApp.hooks.hook('app:suspense:resolve', async () => {
    authStore.loadFromStorage();

    if (authStore.token && !authStore.user) {
      await authStore.fetchMe();
    }

    authStore.hydrated = true;
  });
});
