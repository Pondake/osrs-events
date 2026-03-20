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
    /** true once the client-side auth plugin has finished loading from localStorage */
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
    setToken(token: string) {
      this.token = token;
      if (import.meta.client) {
        localStorage.setItem('auth_token', token);
      }
    },

    setUser(user: User) {
      this.user = user;
    },

    /**
     * Load token from localStorage (called on app init)
     */
    loadFromStorage() {
      if (import.meta.client) {
        const token = localStorage.getItem('auth_token');
        if (token) {
          this.token = token;
        }
      }
    },

    /**
     * Fetch current user info from backend using stored JWT
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
