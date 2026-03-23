<template>
  <u-modal v-model:open="isOpen" :title="$t('tile_editor.title')">
    <template #body>
      <!-- ── Step 1: Choose action ────────────────────────────────── -->
      <div v-if="step === 'choose'" class="flex flex-col gap-3">
        <p class="text-sm text-muted mb-1">{{ $t('tile_editor.choose_action') }}</p>

        <!-- Current task preview -->
        <div v-if="selectedTask" class="flex items-center gap-3 p-3 bg-muted/30 rounded-lg mb-2">
          <img
            v-if="selectedTask.iconUrl"
            :src="selectedTask.iconUrl"
            :alt="selectedTask.title"
            class="h-8 w-8 object-contain flex-shrink-0"
          />

          <u-icon v-else name="i-lucide-image" class="size-6 text-muted flex-shrink-0" />

          <div class="min-w-0">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">
              {{ $t('tile_editor.current_task') }}
            </p>

            <p class="text-sm font-medium">{{ selectedTask.title }}</p>

            <p class="text-sm font-medium">{{ selectedTask.description }}</p>
          </div>
        </div>

        <!-- Option 1: Edit task (icon / title) -->
        <button
          v-if="selectedTask"
          class="w-full cursor-pointer text-left p-4 rounded-lg border border-default hover:bg-muted/30 transition-colors flex items-start gap-3"
          @click="enterEditTask"
        >
          <u-icon name="i-lucide-image-plus" class="size-5 text-primary mt-0.5 flex-shrink-0" />

          <div>
            <p class="font-semibold text-sm">{{ $t('tile_editor.action_edit_task') }}</p>

            <p class="text-xs text-muted mt-0.5">{{ $t('tile_editor.action_edit_task_desc') }}</p>
          </div>
        </button>

        <!-- Option 2: Edit board tile (type / target / override) -->
        <button
          class="w-full cursor-pointer text-left p-4 rounded-lg border border-default hover:bg-muted/30 transition-colors flex items-start gap-3"
          @click="step = 'edit-tile'"
        >
          <u-icon name="i-lucide-settings-2" class="size-5 text-primary mt-0.5 flex-shrink-0" />

          <div>
            <p class="font-semibold text-sm">{{ $t('tile_editor.action_edit_tile') }}</p>

            <p class="text-xs text-muted mt-0.5">{{ $t('tile_editor.action_edit_tile_desc') }}</p>
          </div>
        </button>

        <!-- Option 3: Replace task -->
        <button
          class="w-full cursor-pointer text-left p-4 rounded-lg border border-default hover:bg-muted/30 transition-colors flex items-start gap-3"
          @click="step = 'replace'"
        >
          <u-icon name="i-lucide-refresh-cw" class="size-5 text-amber-500 mt-0.5 flex-shrink-0" />

          <div>
            <p class="font-semibold text-sm">{{ $t('tile_editor.action_replace') }}</p>

            <p class="text-xs text-muted mt-0.5">{{ $t('tile_editor.action_replace_desc') }}</p>
          </div>
        </button>
      </div>

      <!-- ── Step 2a: Edit task (icon + title) ────────────────────── -->
      <div v-else-if="step === 'edit-task'" class="flex flex-col gap-4">
        <u-button
          size="xs"
          color="neutral"
          variant="ghost"
          icon="i-lucide-arrow-left"
          class="self-start -ml-1"
          :label="$t('tile_editor.back')"
          @click="step = 'choose'"
        />

        <task-edit-form v-model="taskEditForm" />
      </div>

      <!-- ── Step 2b: Edit board tile (type / target / override) ──── -->
      <div v-else-if="step === 'edit-tile'" class="flex flex-col gap-4">
        <u-button
          size="xs"
          color="neutral"
          variant="ghost"
          icon="i-lucide-arrow-left"
          class="self-start -ml-1"
          :label="$t('tile_editor.back')"
          @click="step = 'choose'"
        />

        <!-- Current task (read-only reference) -->
        <div v-if="selectedTask" class="flex items-center gap-3 p-2 bg-muted/30 rounded-lg">
          <img
            v-if="selectedTask.iconUrl"
            :src="selectedTask.iconUrl"
            :alt="selectedTask.title"
            class="h-8 w-8 object-contain"
          />

          <p class="text-sm font-medium truncate">{{ selectedTask.title }}</p>
        </div>

        <!-- Title override -->
        <u-form-field :label="$t('tile_editor.title_override')">
          <u-input
            v-model="form.titleOverride"
            :placeholder="selectedTask?.title ?? $t('tile_editor.no_task')"
          />
        </u-form-field>

        <!-- Tile type -->
        <u-form-field :label="$t('tile_editor.tile_type')">
          <u-select
            v-model="form.type"
            :items="tileTypeOptions"
            value-key="value"
            label-key="label"
          />
        </u-form-field>

        <!-- Target position (snake/ladder only) -->
        <u-form-field
          v-if="form.type !== 'NORMAL'"
          :label="$t('tile_editor.target_tile')"
          :description="$t('tile_editor.target_tile_help')"
        >
          <u-input
            v-model.number="form.targetPosition"
            type="number"
            :min="1"
            :max="totalTiles"
            :placeholder="`1 – ${totalTiles}`"
          />
        </u-form-field>
      </div>

      <!-- ── Step 2c: Replace task ─────────────────────────────────── -->
      <div v-else-if="step === 'replace'" class="flex flex-col gap-4">
        <u-button
          size="xs"
          color="neutral"
          variant="ghost"
          icon="i-lucide-arrow-left"
          class="self-start -ml-1"
          :label="$t('tile_editor.back')"
          @click="step = selectedTask ? 'choose' : 'replace'"
        />

        <u-form-field :label="$t('tile_editor.task_placeholder')">
          <u-input
            v-model="taskSearch"
            :placeholder="$t('tile_editor.task_placeholder')"
            icon="i-lucide-search"
            @input="onTaskSearch"
          />

          <!-- Search results -->
          <div
            v-if="taskResults.length && taskSearch"
            class="border border-default rounded-lg mt-1 max-h-48 overflow-y-auto"
          >
            <button
              v-for="task in taskResults"
              :key="task.id"
              class="flex items-center gap-3 w-full px-3 py-2 hover:bg-muted/50 text-left"
              @click="selectTask(task)"
            >
              <img
                v-if="task.iconUrl"
                :src="task.iconUrl"
                :alt="task.title"
                class="h-6 w-6 object-contain"
                loading="lazy"
              />

              <u-icon v-else name="i-lucide-image" class="size-5 text-muted" />

              <span class="text-sm">{{ task.title }}</span>
            </button>
          </div>

          <!-- Selected task preview -->
          <div v-if="selectedTask" class="flex items-center gap-3 mt-2 p-2 bg-muted/30 rounded-lg">
            <img
              v-if="selectedTask.iconUrl"
              :src="selectedTask.iconUrl"
              :alt="selectedTask.title"
              class="h-8 w-8 object-contain"
            />

            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ selectedTask.title }}</p>
            </div>

            <u-button
              icon="i-lucide-x"
              color="neutral"
              variant="ghost"
              size="xs"
              @click="clearTask"
            />
          </div>
        </u-form-field>

        <!-- Create new task option -->
        <u-separator :label="$t('common.or')" />

        <button
          class="w-full cursor-pointer text-left p-4 rounded-lg border border-default hover:bg-muted/30 transition-colors flex items-start gap-3"
          @click="enterCreateTask"
        >
          <u-icon name="i-lucide-plus-circle" class="size-5 text-primary mt-0.5 flex-shrink-0" />

          <div>
            <p class="font-semibold text-sm">{{ $t('tile_editor.action_create_task') }}</p>

            <p class="text-xs text-muted mt-0.5">{{ $t('tile_editor.action_create_task_desc') }}</p>
          </div>
        </button>
      </div>

      <!-- ── Step 2d: Create new task ──────────────────────────────── -->
      <div v-else-if="step === 'create-task'" class="flex flex-col gap-4">
        <u-button
          size="xs"
          color="neutral"
          variant="ghost"
          icon="i-lucide-arrow-left"
          class="self-start -ml-1"
          :label="$t('tile_editor.back')"
          @click="step = 'replace'"
        />

        <task-edit-form v-model="createTaskForm" />
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between w-full">
        <!-- Delete always visible -->
        <u-button
          color="error"
          variant="ghost"
          icon="i-lucide-trash-2"
          :label="$t('common.delete')"
          @click="deleteTile"
        />

        <div class="flex gap-2">
          <u-button
            color="neutral"
            variant="ghost"
            :label="$t('common.cancel')"
            @click="isOpen = false"
          />

          <!-- Save task (edit-task step) -->
          <u-button
            v-if="step === 'edit-task'"
            color="primary"
            :loading="saving"
            :label="$t('common.save')"
            @click="saveTask"
          />

          <!-- Save tile settings / replace -->
          <u-button
            v-else-if="step === 'edit-tile' || step === 'replace'"
            color="primary"
            :loading="saving"
            :label="$t('common.save')"
            @click="saveTile"
          />

          <!-- Create new task -->
          <u-button
            v-else-if="step === 'create-task'"
            color="primary"
            :loading="saving"
            :label="$t('common.create')"
            @click="createNewTask"
          />
        </div>
      </div>
    </template>
  </u-modal>
</template>

<script setup lang="ts">
import type { TaskFormData } from '~/components/Task/EditForm.vue'
import type { TaskEntity, TileType } from '~/types/graphql'

import TaskEditForm from '~/components/Task/EditForm.vue'

// TileData is the editor's working shape — a partial TileEntity used for create/edit forms.
interface TileData {
  id?: string
  position: number
  boardId: string
  task: TaskEntity | null
  titleOverride: string | null
  type: TileType
  targetPosition: number | null
}

const props = defineProps<{
  tile: TileData | null;
  totalTiles: number;
  open: boolean;
}>();
const emit = defineEmits<{
  'update:open': [value: boolean];
  saved: [];
  deleted: [];
  'task-updated': [{ tileId: string | undefined; task: TaskEntity }];
}>();

const { t } = useI18n();
const toast = useToast();

const isOpen = computed({
  get: () => props.open,
  set: val => emit('update:open', val),
});

type Step = 'choose' | 'edit-task' | 'edit-tile' | 'replace' | 'create-task';
const step = ref<Step>('choose');

const tileTypeOptions = [
  { value: 'NORMAL', label: t('tile_editor.type_normal') },
  { value: 'SNAKE', label: t('tile_editor.type_snake') },
  { value: 'LADDER', label: t('tile_editor.type_ladder') },
];

// Board-tile form (type / targetPosition / titleOverride)
const form = reactive({
  titleOverride: '' as string,
  type: 'NORMAL' as TileType,
  targetPosition: null as number | null,
});

// Task edit form (icon / title / description)
const taskEditForm = ref<TaskFormData>({
  title: '',
  iconUrl: '',
  description: '',
});

// Create task form (new task inline)
const createTaskForm = ref<TaskFormData>({
  title: '',
  iconUrl: '',
  description: '',
});

const taskSearch = ref('');
const taskResults = ref<TaskEntity[]>([]);
const selectedTask = ref<TaskEntity | null>(null);
const saving = ref(false);

// Populate form when tile prop changes
watch(
  () => props.tile,
  tile => {
    if (!tile) return;
    form.titleOverride = tile.titleOverride ?? '';
    form.type = tile.type;
    form.targetPosition = tile.targetPosition !== null ? tile.targetPosition + 1 : null; // 1-based display
    selectedTask.value = tile.task;
    taskSearch.value = tile.task?.title ?? '';
    step.value = tile.task ? 'choose' : 'replace';
  },
  { immediate: true },
);

// Enter create-task step: reset the form
function enterCreateTask() {
  createTaskForm.value.title = '';
  createTaskForm.value.iconUrl = '';
  createTaskForm.value.description = '';
  step.value = 'create-task';
}

// Populate task edit form when entering that step
function enterEditTask() {
  if (!selectedTask.value) return;
  taskEditForm.value.title = selectedTask.value.title;
  taskEditForm.value.iconUrl = selectedTask.value.iconUrl ?? '';
  taskEditForm.value.description = '';
  step.value = 'edit-task';
}

const CREATE_TASK_GQL = `mutation CreateTask($input: CreateTaskInput!) {
  createTask(input: $input) { id title iconUrl }
}`;

const SEARCH_TASKS = `query SearchTasks($search: String) {
  tasks(search: $search) { id title iconUrl }
}`;

const UPDATE_TASK_GQL = `mutation UpdateTask($id: ID!, $input: UpdateTaskInput!) {
  updateTask(id: $id, input: $input) { id title iconUrl }
}`;

const UPSERT_TILE = `mutation UpsertTile($input: UpsertTileInput!) {
  upsertTile(input: $input) { id position type targetPosition titleOverride task { id title iconUrl } }
}`;

const DELETE_TILE = `mutation DeleteTile($id: ID!) {
  deleteTile(id: $id) { id }
}`;

async function onTaskSearch() {
  if (taskSearch.value.length < 2) {
    taskResults.value = [];
    return;
  }
  try {
    const result = await useGqlMutation<{ tasks: TaskEntity[] }>(SEARCH_TASKS, {
      search: taskSearch.value,
    });
    taskResults.value = result.tasks ?? [];
  } catch {
    taskResults.value = [];
  }
}

function selectTask(task: TaskEntity) {
  selectedTask.value = task;
  taskSearch.value = task.title;
  taskResults.value = [];
  step.value = 'choose';
}

function clearTask() {
  selectedTask.value = null;
  taskSearch.value = '';
  taskResults.value = [];
}

/** Save the underlying task (icon / title / description) — updates in-place, no full reload */
async function saveTask() {
  if (!selectedTask.value?.id) return;
  saving.value = true;
  try {
    const result = await useGqlMutation<{ updateTask: TaskEntity }>(UPDATE_TASK_GQL, {
      id: selectedTask.value.id,
      input: {
        title: taskEditForm.value.title || undefined,
        iconUrl: taskEditForm.value.iconUrl || null,
        description: taskEditForm.value.description || null,
      },
    });
    const updatedTask = { ...selectedTask.value, ...result.updateTask } as TaskEntity;
    selectedTask.value = updatedTask;
    toast.add({ title: t('tile_editor.task_saved'), color: 'success' });
    // Emit task-updated so the parent can patch the tile in-place without a full board refresh
    emit('task-updated', { tileId: props.tile?.id, task: updatedTask });
    isOpen.value = false;
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    saving.value = false;
  }
}

/** Save board-tile settings (type / target / titleOverride / taskId for replace) */
async function saveTile() {
  if (!props.tile) return;
  saving.value = true;
  try {
    await useGqlMutation(UPSERT_TILE, {
      input: {
        boardId: props.tile.boardId,
        position: props.tile.position,
        taskId: selectedTask.value?.id ?? null,
        titleOverride: form.titleOverride || null,
        type: form.type,
        targetPosition: form.targetPosition !== null ? form.targetPosition - 1 : null, // back to 0-based
      },
    });
    toast.add({ title: t('common.save'), color: 'success' });
    emit('saved');
    isOpen.value = false;
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    saving.value = false;
  }
}

/** Create a brand-new task and auto-assign it to this tile */
async function createNewTask() {
  if (!props.tile || !createTaskForm.value.title.trim()) return;
  saving.value = true;
  try {
    const result = await useGqlMutation<{ createTask: TaskEntity }>(CREATE_TASK_GQL, {
      input: {
        title: createTaskForm.value.title.trim(),
        iconUrl: createTaskForm.value.iconUrl || null,
        description: createTaskForm.value.description || null,
      },
    });
    const newTask = result.createTask;
    // Auto-assign the new task to this tile
    await useGqlMutation(UPSERT_TILE, {
      input: {
        boardId: props.tile.boardId,
        position: props.tile.position,
        taskId: newTask.id,
        titleOverride: form.titleOverride || null,
        type: form.type,
        targetPosition: form.targetPosition !== null ? form.targetPosition - 1 : null,
      },
    });
    selectedTask.value = newTask;
    toast.add({ title: t('tile_editor.task_created'), color: 'success' });
    emit('saved');
    isOpen.value = false;
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    saving.value = false;
  }
}

async function deleteTile() {
  if (!props.tile?.id) {
    isOpen.value = false;
    return;
  }
  try {
    await useGqlMutation(DELETE_TILE, { id: props.tile.id });
    toast.add({ title: t('common.delete'), color: 'success' });
    emit('deleted');
    isOpen.value = false;
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  }
}
</script>
