import type { ComputedRef, MaybeRef } from 'vue';

import { useAuthStore } from '~/stores/auth';

type Variables = MaybeRef<Record<string, unknown>> | ComputedRef<Record<string, unknown>>;

interface UseGqlOptions {
  /** Set to false to skip the initial fetch during SSR (for auth-protected queries). Default: true */
  server?: boolean;
}

/**
 * GraphQL composable backed by useAsyncData — SSR-safe with payload transfer.
 *
 * ## How loading works
 *
 * `lazy: true` is set so the `await` in callers resolves immediately on the
 * client without blocking Vue's Suspense boundary.  This means:
 *
 * - **Hard reload / SSR**: data is fetched on the server and embedded in the
 *   Nuxt HTML payload.  On hydration the client reads from the payload →
 *   `pending = false` and `data` is populated instantly → board renders.
 *
 * - **Client-side navigation** (no SSR payload for this route yet): the
 *   `await useAsyncData(…, { lazy: true })` resolves before the fetch, so the
 *   page setup finishes immediately.  The component renders right away with
 *   `pending = true` → skeleton shows → fetch completes → board renders.
 *
 * Without `lazy: true` the Suspense boundary blocks rendering until the fetch
 * finishes, producing an invisible empty state before the board appears.
 *
 * Pass `{ server: false }` to skip the SSR fetch for auth-protected queries.
 */
export async function useGql<T = Record<string, unknown>>(
  query: string,
  variables?: Variables,
  options?: UseGqlOptions,
): Promise<{
  data: Ref<T | null>;
  pending: Ref<boolean>;
  error: Ref<Error | null>;
  refresh: () => Promise<void>;
}> {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();

  // Stable key: normalised query + initial variable snapshot.
  // Must be identical on server and client so the payload is matched correctly.
  const snapshotVars = variables ? toValue(variables) : {};
  const normalised = query.trim().replace(/\s+/g, ' ');
  const cacheKey = `gql:${normalised.slice(0, 80)}:${JSON.stringify(snapshotVars)}`;

  const fetcher = async (): Promise<T> => {
    const currentVars = variables ? toValue(variables) : undefined;
    const response = await $fetch<{ data: T; errors?: Array<{ message: string }> }>(
      config.public.graphqlUrl,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}),
        },
        body: { query, variables: currentVars },
      },
    );
    if (response.errors?.length) {
      throw new Error(response.errors.map(e => e.message).join('; '));
    }
    return response.data;
  };

  const { data, pending, error, refresh } = await useAsyncData<T>(cacheKey, fetcher, {
    server: options?.server !== false,
    // lazy: true — resolves the await immediately on the client so Vue's
    // Suspense boundary is never blocked.  On SSR this has no effect; Nuxt
    // still waits for all useAsyncData fetchers before rendering the HTML.
    lazy: true,
  });

  return {
    data: data as Ref<T | null>,
    pending: pending as Ref<boolean>,
    error: error as unknown as Ref<Error | null>,
    refresh: async () => {
      await refresh();
    },
  };
}

/**
 * One-shot GraphQL mutation (or query) using $fetch.
 * Not reactive — use for mutations and imperative queries.
 */
export async function useGqlMutation<T = Record<string, unknown>>(
  mutation: string,
  variables?: Record<string, unknown>,
): Promise<T> {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();

  const response = await $fetch<{ data: T; errors?: Array<{ message: string }> }>(
    config.public.graphqlUrl,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}),
      },
      body: { query: mutation, variables },
    },
  );

  if (response.errors?.length) {
    throw new Error(response.errors.map(e => e.message).join('; '));
  }

  return response.data;
}
