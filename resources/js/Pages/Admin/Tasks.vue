<template>
    <Head :title="$t('admin.tasks_title')" />

    <admin-layout current="tasks" :title="$t('settings.nav_admin_tasks')" :description="$t('admin.tasks_subtitle')">
        <template #actions>
            <u-button color="primary" icon="i-lucide-plus" size="sm" :label="$t('admin.create_task')" @click="openCreate" />
        </template>

        <div class="flex flex-col sm:flex-row gap-3">
            <u-input
                v-model="search"
                :placeholder="$t('admin.search_tasks_placeholder')"
                icon="i-lucide-search"
                class="w-full sm:max-w-sm"
                :ui="{ base: 'max-sm:min-h-11' }"
            />

            <!-- One list, not two toggles: the three wiki states and "no
                 icon" are all the same question — which rows are still
                 missing something — and only ever asked one at a time. -->
            <u-select
                v-model="filter"
                :items="filterOptions"
                class="w-full sm:max-w-56"
                :ui="{ base: 'max-sm:min-h-11', value: 'pe-6' }"
            >
                <template #item-leading="{ item }">
                    <u-icon :name="item.icon" class="size-4" />
                </template>
            </u-select>
        </div>

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
                        class="inline-flex items-center justify-center text-muted hover:text-primary transition-colors p-1.5 max-sm:min-h-11 max-sm:min-w-11"
                        :title="$t('tile_editor.open_wiki_page')"
                        :aria-label="$t('tile_editor.open_wiki_page')"
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
            <p v-if="!tasks.length" class="px-4 py-8 text-center text-muted text-sm">{{ emptyMessage }}</p>
        </div>

        <client-only>
            <task-settings-modal v-model:open="showModal" :task="editingTask" />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';
import ConfirmPopover from '@/Components/ConfirmPopover.vue';

const TaskSettingsModal = defineAsyncComponent(() => import('@/Components/TaskSettingsModal.vue'));

const props = defineProps({
    tasks: { type: Array, required: true },
    search: { type: String, default: '' },
    filter: { type: String, default: '' },
});

// 'all' rather than null or '': it is a real first entry in the list, so the
// control always shows a chosen state instead of a placeholder, and there is
// nothing to clear. The server sees no `filter` param for it.
const ALL = 'all';

const search = ref(props.search);
const filter = ref(props.filter || ALL);
const showModal = ref(false);
const editingTask = ref(null);

const filterOptions = computed(() => [
    { value: ALL, label: trans('admin.task_filter_all'), icon: 'i-lucide-list' },
    { value: 'with_wiki', label: trans('admin.task_filter_with_wiki'), icon: 'i-lucide-book-open' },
    { value: 'without_wiki', label: trans('admin.task_filter_without_wiki'), icon: 'i-lucide-book-dashed' },
    { value: 'without_icon', label: trans('admin.task_filter_without_icon'), icon: 'i-lucide-image-off' },
]);

// "Nothing matched" and "nothing is missing" are opposite news. A search
// term decides the wording first: with one, an empty list is about the term,
// whatever the filter says.
const emptyMessage = computed(() => {
    if (search.value) {
        return trans('admin.tasks_empty_search');
    }

    return {
        with_wiki: trans('admin.tasks_empty_with_wiki'),
        without_wiki: trans('admin.tasks_empty_without_wiki'),
        without_icon: trans('admin.tasks_empty_without_icon'),
    }[filter.value] ?? trans('admin.no_tasks');
});

// One shared timer for both controls: it is a single "filters changed"
// signal either way, and two timers could race into two visits for one
// change. Same pattern as Admin/Audit.vue.
let timer;
watch([search, filter], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/admin/tasks',
            {
                search: search.value || undefined,
                filter: filter.value === ALL ? undefined : filter.value,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

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
