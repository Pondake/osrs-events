import type { ComputedRef, MaybeRef } from 'vue';
import type { TaskEntity, CreateTaskInput, UpdateTaskInput } from '~/types/graphql';

// ─── Queries & mutations ─────────────────────────────────────────────────────

const TASK_FIELDS = `id title iconUrl description createdAt updatedAt`;

const TASKS_QUERY = `
  query Tasks($search: String) {
    tasks(search: $search) { ${TASK_FIELDS} }
  }
`;

const CREATE_TASK_MUTATION = `
  mutation CreateTask($input: CreateTaskInput!) {
    createTask(input: $input) { ${TASK_FIELDS} }
  }
`;

const UPDATE_TASK_MUTATION = `
  mutation UpdateTask($id: ID!, $input: UpdateTaskInput!) {
    updateTask(id: $id, input: $input) { ${TASK_FIELDS} }
  }
`;

const DELETE_TASK_MUTATION = `
  mutation DeleteTask($id: ID!) {
    deleteTask(id: $id) { id }
  }
`;

// ─── Composable ──────────────────────────────────────────────────────────────

/**
 * Reactive task list with search support.
 * Pass a reactive/computed search string to auto-refresh on change.
 *
 * @example
 * const search = ref('')
 * const { tasks, refresh } = await useTasks(computed(() => search.value))
 */
export async function useTasks(
  search?: MaybeRef<string | undefined> | ComputedRef<string | undefined>,
) {
  const vars = computed(() => ({ search: toValue(search) || undefined }));
  const { data, pending, error, refresh } = await useGql<{ tasks: TaskEntity[] }>(
    TASKS_QUERY,
    vars,
  );

  async function createTask(input: CreateTaskInput): Promise<TaskEntity> {
    const result = await useGqlMutation<{ createTask: TaskEntity }>(CREATE_TASK_MUTATION, {
      input,
    });
    return result.createTask;
  }

  async function updateTask(id: string, input: UpdateTaskInput): Promise<TaskEntity> {
    const result = await useGqlMutation<{ updateTask: TaskEntity }>(UPDATE_TASK_MUTATION, {
      id,
      input,
    });
    return result.updateTask;
  }

  async function deleteTask(id: string): Promise<TaskEntity> {
    const result = await useGqlMutation<{ deleteTask: TaskEntity }>(DELETE_TASK_MUTATION, { id });
    return result.deleteTask;
  }

  return {
    tasks: computed(() => data.value?.tasks ?? []),
    pending,
    error,
    refresh,
    createTask,
    updateTask,
    deleteTask,
  };
}
