import { createClient, cacheExchange, fetchExchange } from '@urql/vue';

import { useAuthStore } from '~/stores/auth';

let client: ReturnType<typeof createClient> | null = null;

/**
 * Returns a configured urql GraphQL client with Bearer token injection
 */
export function useGraphQL() {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();

  if (!client) {
    client = createClient({
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
  }

  return client;
}
