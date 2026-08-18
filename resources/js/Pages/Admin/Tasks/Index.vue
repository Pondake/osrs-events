<template>
    <Head :title="$t('admin.tasks_title')" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <h1 class="text-3xl font-bold text-highlighted">{{ $t('admin.tasks_title') }}</h1>
                    <u-button color="primary" icon="i-lucide-plus" :label="$t('admin.create_task')" @click="openCreate" />
                </div>

                <u-input v-model="search" :placeholder="$t('admin.search_tasks_placeholder')" icon="i-lucide-search" class="w-full max-w-sm mb-6" @update:model-value="doSearch" />

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
                            <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" @click="openEdit(task)" />
                            <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" @click="destroyTask(task)" />
                        </div>
                    </div>
                    <p v-if="!tasks.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_tasks') }}</p>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <task-settings-modal v-model:open="showModal" :task="editingTask" />
        </client-only>
    </u-main>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';

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

function destroyTask(task) {
    router.delete(`/admin/tasks/${task.id}`, { preserveScroll: true });
}
</script>
