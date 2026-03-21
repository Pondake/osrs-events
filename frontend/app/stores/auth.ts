import { defineStore } from 'pinia';

export interface User {
  id: string;
  discordId: string;
  discordUsername: string;
  nickname: string | null;
  avatarUrl: string | null;
  roles: string[];
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: null as string | null,
    loading: false,
    /** true once auth has been initialized (user fetched or determined to be absent) */
    hydrated: false,
  }),

  getters: {
    isAuthenticated: state => !!state.user && !!state.token,
    isAdmin: state => state.user?.roles?.includes('ADMIN') ?? false,
    isEditor: state => state.user?.roles?.includes('EDITOR') ?? false,
    avatarUrl: state => state.user?.avatarUrl ?? null,
    /** Returns the user's custom nickname if set, otherwise their Discord username */
    displayName: state => state.user?.nickname || state.user?.discordUsername || null,
  },

  actions: {
    /**
     * Persist the JWT to both the auth cookie (for SSR) and localStorage (legacy).
     * Only ever called on the client (after Discord OAuth callback).
     */
    setToken(token: string) {
      this.token = token;
      if (import.meta.client) {
        // Write to cookie so the server can read it on next SSR request (7-day expiry)
        const expires = new Date();
        expires.setDate(expires.getDate() + 7);
        document.cookie = `auth_token=${token}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
        localStorage.setItem('auth_token', token);
      }
    },

    setUser(user: User) {
      this.user = user;
    },

    /**
     * Load token from localStorage (client-only fallback for users who logged in
     * before the cookie-based auth was introduced).
     */
    loadFromStorage() {
      if (import.meta.client) {
        const token = localStorage.getItem('auth_token');
        if (token && !this.token) {
          this.token = token;
          // Migrate to cookie so SSR works on subsequent requests
          const expires = new Date();
          expires.setDate(expires.getDate() + 7);
          document.cookie = `auth_token=${token}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
        }
      }
    },

    /**
     * Fetch current user info from the backend using the stored JWT.
     */
    async fetchMe() {
      if (!this.token) return;
      this.loading = true;
      try {
        const config = useRuntimeConfig();
        const data = await $fetch<User>(
          `${config.public.graphqlUrl.replace('/graphql', '')}/auth/me`,
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          },
        );
        this.user = data;
      } catch {
        // Token is invalid or expired
        this.logout();
      } finally {
        this.loading = false;
      }
    },

    logout() {
      this.user = null;
      this.token = null;
      if (import.meta.client) {
        // Clear cookie
        document.cookie =
          'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
        localStorage.removeItem('auth_token');
      }
    },

    /**
     * Redirect to Discord OAuth login
     */
    loginWithDiscord() {
      const config = useRuntimeConfig();
      const backendUrl = config.public.graphqlUrl.replace('/graphql', '');
      window.location.href = `${backendUrl}/auth/discord`;
    },
  },
});
