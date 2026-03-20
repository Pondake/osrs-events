import type { ComputedRef, MaybeRef } from 'vue';

import { useAuthStore } from '~/stores/auth';

type Variables = MaybeRef<Record<string, unknown>> | ComputedRef<Record<string, unknown>>;

/**
 * GraphQL composable using $fetch — SSR-compatible, no provider needed.
 * Accepts plain objects or reactive refs/computed for variables.
 * When refresh() is called, it re-reads the current variable values.
 */
export async function useGql<T = Record<string, unknown>>(
  query: string,
  variables?: Variables,
): Promise<{
  data: Ref<T | null>;
  pending: Ref<boolean>;
  error: Ref<Error | null>;
  refresh: () => Promise<void>;
}> {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();

  const data = ref<T | null>(null) as Ref<T | null>;
  const pending = ref(true);
  const error = ref<Error | null>(null);

  const execute = async () => {
    pending.value = true;
    error.value = null;
    try {
      // Unwrap reactive variables on each execution
      const resolvedVars = variables ? toValue(variables) : undefined;
      const response = await $fetch<{ data: T; errors?: Array<{ message: string }> }>(
        config.public.graphqlUrl,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...(authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}),
          },
          body: { query, variables: resolvedVars },
        },
      );
      if (response.errors?.length) {
        error.value = new Error(response.errors[0].message);
      } else {
        data.value = response.data;
      }
    } catch (e) {
      error.value = e as Error;
    } finally {
      pending.value = false;
    }
  };

  await execute();

  return { data, pending, error, refresh: execute };
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
    throw new Error(response.errors[0].message);
  }

  return response.data;
}
