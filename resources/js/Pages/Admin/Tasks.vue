<template>
    <Head :title="$t('admin.tasks_title')" />

    <admin-layout current="tasks" :title="$t('settings.nav_admin_tasks')" :description="$t('admin.tasks_subtitle')">
        <template #actions>
            <u-button color="primary" icon="i-lucide-plus" size="sm" :label="$t('admin.create_task')" @click="openCreate" />
        </template>

        <u-input v-model="search" :placeholder="$t('admin.search_tasks_placeholder')" icon="i-lucide-search" class="w-full sm:max-w-sm" @update:model-value="doSearch" />

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="task in tasks" :key="task.id" class="flex items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <u-avatar :src="task.icon_url ?? undefined" size="xs" />
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ task.title }}</div>
                        <div v-if="task.description" class="text-xs text-muted truncate">{{ task.description }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a
                        v-if="task.wiki_url"
                        :href="task.wiki_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-muted hover:text-primary transition-colors p-1.5"
                        :title="$t('tile_editor.open_wiki_page')"
                    >
                        <u-icon name="i-lucide-external-link" class="size-4" />
                    </a>
                    <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" :aria-label="$t('common.edit')" @click="openEdit(task)" />
                    <confirm-popover
                        :message="$t('admin.task_delete_confirm', { title: task.title })"
                        :confirm-label="$t('common.delete')"
                        :loading="deletingTaskId === task.id"
                        :note-placeholder="$t('admin.task_delete_note_placeholder')"
                        @confirm="(note, done) => destroyTask(task, note, done)"
                    >
                        <template #default>
                            <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" />
                        </template>
                    </confirm-popover>
                </div>
            </div>
            <p v-if="!tasks.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_tasks') }}</p>
        </div>

        <client-only>
            <task-settings-modal v-model:open="showModal" :task="editingTask" />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';
import ConfirmPopover from '@/Components/ConfirmPopover.vue';

const TaskSettingsModal = defineAsyncComponent(() => import('@/Components/TaskSettingsModal.vue'));

const props = defineProps({
    tasks: { type: Array, required: true },
    search: { type: String, default: '' },
});

const search = ref(props.search);
const showModal = ref(false);
const editingTask = ref(null);

function doSearch(value) {
    router.get('/admin/tasks', { search: value }, { preserveState: true, replace: true });
}

function openCreate() {
    editingTask.value = null;
    showModal.value = true;
}

function openEdit(task) {
    editingTask.value = task;
    showModal.value = true;
}

const deletingTaskId = ref(null);

function destroyTask(task, note, done) {
    deletingTaskId.value = task.id;

    router.delete(`/admin/tasks/${task.id}`, {
        data: { note: note || null },
        preserveScroll: true,
        onSuccess: async () => {
            // Dynamic import, not a top-level one: useToast() reaches
            // @nuxt/ui's `#imports` virtual specifier, which only resolves
            // through the ui() Vite plugin's bundler pipeline — importing it
            // eagerly crashes Admin pages' place in the SSR module graph even
            // though the admin shell itself renders behind <ClientOnly>. Same
            // pattern AppRoot.vue uses for its own toast calls.
            const { useToast } = await import('@nuxt/ui/composables/useToast');
            useToast().add({
                id: `task-deleted-${task.id}`,
                title: trans('admin.task_deleted'),
                color: 'success',
                actions: [{ label: trans('common.undo'), onClick: () => restoreTask(task) }],
            });
        },
        onFinish: () => {
            deletingTaskId.value = null;
            done?.();
        },
    });
}

function restoreTask(task) {
    router.post(`/admin/tasks/${task.id}/restore`, {}, { preserveScroll: true });
}
</script>
