<template>
  <nuxt-layout :title="$t('admin.tasks_title')" :description="$t('admin.tasks_subtitle')">
    <u-page-body>
      <u-container>
        <div class="flex gap-3 mb-6">
          <u-input
            v-model="searchQuery"
            :placeholder="$t('common.search')"
            icon="i-lucide-search"
            class="flex-1"
          />

          <u-button icon="i-lucide-plus" color="primary" :label="$t('common.create')" @click="openCreateModal" />
        </div>

        <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <u-skeleton v-for="i in 6" :key="i" class="h-24" />
        </div>

        <u-alert
          v-else-if="error"
          color="error"
          icon="i-lucide-alert-circle"
          :title="$t('errors.generic')"
        />

        <div v-else-if="tasks.length === 0" class="text-center py-12 text-muted">
          <u-icon name="i-lucide-clipboard-list" class="text-5xl mb-4 block mx-auto" />
          <p>{{ $t('admin.no_tasks') }}</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <u-card
            v-for="task in tasks"
            :key="task.id"
            class="osrs-border cursor-pointer hover:ring-2 hover:ring-primary transition-all"
            @click="openEditModal(task)"
          >
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 shrink-0 flex items-center justify-center rounded-md bg-muted overflow-hidden">
                <img
                  v-if="task.iconUrl"
                  :src="task.iconUrl"
                  :alt="task.title"
                  class="w-10 h-10 object-contain image-rendering-pixelated"
                />
                <u-icon v-else name="i-lucide-image" class="text-2xl text-muted" />
              </div>

              <div class="flex-1 min-w-0">
                <p class="font-semibold osrs-font truncate">{{ task.title }}</p>
                <p v-if="task.description" class="text-xs text-muted truncate">
                  {{ task.description }}
                </p>
              </div>

              <u-button
                icon="i-lucide-pencil"
                color="neutral"
                variant="ghost"
                size="xs"
                @click.stop="openEditModal(task)"
              />
            </div>
          </u-card>
        </div>
      </u-container>
    </u-page-body>

    <!-- Create/Edit Modal -->
    <u-modal v-model:open="showModal">
      <template #content>
        <u-card class="osrs-border">
          <template #header>
            <h3 class="text-lg font-semibold osrs-font">
              {{ editingTask ? $t('common.edit') : $t('common.create') }}
            </h3>
          </template>

          <task-edit-form v-model="taskForm" />

          <template #footer>
            <div class="flex justify-between items-center">
              <u-button
                v-if="editingTask"
                icon="i-lucide-trash"
                color="error"
                variant="ghost"
                :loading="deleting"
                :label="$t('common.delete')"
                @click="doDeleteTask"
              />
              <div v-else />

              <div class="flex gap-2">
                <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="showModal = false" />
                <u-button color="primary" :loading="saving" icon="i-lucide-check" :label="$t('common.save')" @click="saveTask" />
              </div>
            </div>
          </template>
        </u-card>
      </template>
    </u-modal>
  </nuxt-layout>
</template>

<script setup lang="ts">
import type { TaskEntity } from '~/types/graphql'
import { useTasks } from '~/composables/useTasks'
import type { TaskFormData } from '~/components/Task/EditForm.vue'

definePageMeta({ middleware: ['admin'] })

const { t } = useI18n()
const toast = useToast()

const searchQuery = ref('')
const debouncedSearch = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, val => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { debouncedSearch.value = val }, 350)
})

const { tasks, pending, error, refresh, createTask, updateTask, deleteTask } = await useTasks(
  computed(() => debouncedSearch.value || undefined),
)

// ─── Modal state ──────────────────────────────────────────────────────────────

const showModal = ref(false)
const editingTask = ref<TaskEntity | null>(null)
const saving = ref(false)
const deleting = ref(false)

const taskForm = ref<TaskFormData>({ title: '', iconUrl: '', description: '' })

function openCreateModal() {
  editingTask.value = null
  taskForm.value = { title: '', iconUrl: '', description: '' }
  showModal.value = true
}

function openEditModal(task: TaskEntity) {
  editingTask.value = task
  taskForm.value = {
    title: task.title,
    iconUrl: task.iconUrl ?? '',
    description: task.description ?? '',
  }
  showModal.value = true
}

async function saveTask() {
  if (!taskForm.value.title.trim()) {
    toast.add({ title: t('errors.generic'), color: 'error' })
    return
  }
  saving.value = true
  try {
    const input = {
      title: taskForm.value.title.trim(),
      iconUrl: taskForm.value.iconUrl.trim() || null,
      description: taskForm.value.description.trim() || null,
    }
    if (editingTask.value) {
      await updateTask(editingTask.value.id, input)
      toast.add({ title: t('admin.task_updated'), color: 'success' })
    } else {
      await createTask(input as any)
      toast.add({ title: t('admin.task_created'), color: 'success' })
    }
    showModal.value = false
    await refresh()
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error' })
  } finally {
    saving.value = false
  }
}

async function doDeleteTask() {
  if (!editingTask.value) return
  deleting.value = true
  try {
    await deleteTask(editingTask.value.id)
    toast.add({ title: t('admin.task_deleted'), color: 'success' })
    showModal.value = false
    await refresh()
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error' })
  } finally {
    deleting.value = false
  }
}
</script>
