import { createClient, cacheExchange, fetchExchange } from '@urql/vue';

import { useAuthStore } from '~/stores/auth';

/**
 * Nuxt plugin: provide urql GraphQL client to the app
 */
export default defineNuxtPlugin(nuxtApp => {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();

  const client = createClient({
    url: config.public.graphqlUrl,
    exchanges: [cacheExchange, fetchExchange],
    fetchOptions: () => {
      const token = authStore.token;
      return {
        headers: {
          Authorization: token ? `Bearer ${token}` : '',
        },
      };
    },
  });

  nuxtApp.vueApp.use({ install: app => app.provide('$urql', client) });

  return {
    provide: {
      urql: client,
    },
  };
});
