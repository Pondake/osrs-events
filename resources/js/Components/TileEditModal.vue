<template>
    <u-modal v-model:open="isOpen" :title="`Edit tile ${position + 1}`">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field label="Task">
                    <u-input v-model="taskSearch" placeholder="Search tasks…" icon="i-lucide-search" class="w-full" @update:model-value="debouncedSearch" />
                    <div v-if="taskResults.length" class="mt-2 divide-y divide-default rounded-md ring ring-default max-h-48 overflow-y-auto">
                        <button
                            v-for="task in taskResults"
                            :key="task.id"
                            type="button"
                            class="w-full flex items-center gap-2 px-3 py-2 text-left text-sm hover:bg-elevated"
                            @click="selectTask(task)"
                        >
                            <u-avatar :src="task.icon_url ?? undefined" size="3xs" />
                            <span class="truncate">{{ task.title }}</span>
                        </button>
                    </div>
                    <div v-if="selectedTask" class="mt-2 flex items-center gap-2 p-2 rounded-md bg-elevated">
                        <u-avatar :src="selectedTask.icon_url ?? undefined" size="xs" />
                        <span class="text-sm flex-1 truncate">{{ selectedTask.title }}</span>
                        <u-button icon="i-lucide-x" size="xs" color="neutral" variant="ghost" @click="selectedTask = null" />
                    </div>
                </u-form-field>

                <u-form-field label="Title override" description="Shown instead of the task title, if set.">
                    <u-input v-model="form.title_override" class="w-full" />
                </u-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <u-form-field label="Tile type">
                        <u-select v-model="form.type" :items="typeOptions" class="w-full" />
                    </u-form-field>
                    <u-form-field v-if="form.type !== 'NORMAL'" label="Target position">
                        <u-input v-model.number="form.target_position" type="number" min="0" class="w-full" />
                    </u-form-field>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between gap-2 w-full">
                <u-button v-if="tile" color="error" variant="outline" label="Clear tile" @click="clearTile" />
                <div class="flex gap-2 ml-auto">
                    <u-button color="neutral" variant="outline" label="Cancel" @click="isOpen = false" />
                    <u-button color="primary" label="Save" :loading="form.processing" @click="submit" />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
    boardId: { type: String, required: true },
    position: { type: Number, required: true },
    tile: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const typeOptions = [
    { label: 'Normal', value: 'NORMAL' },
    { label: 'Snake (slides back)', value: 'SNAKE' },
    { label: 'Ladder (jumps ahead)', value: 'LADDER' },
];

const taskSearch = ref('');
const taskResults = ref([]);
const selectedTask = ref(null);

function blankForm() {
    return { title_override: '', type: 'NORMAL', target_position: null, task_id: null };
}

const form = useForm(blankForm());

watch(
    () => [props.tile, props.open],
    () => {
        if (!props.open) return;
        const t = props.tile;
        form.defaults(t ? {
            title_override: t.title_override ?? '',
            type: t.type,
            target_position: t.target_position,
            task_id: t.task?.id ?? null,
        } : blankForm()).reset();
        selectedTask.value = t?.task ?? null;
        taskSearch.value = '';
        taskResults.value = [];
    },
    { immediate: true },
);

let searchTimeout;
function debouncedSearch(value) {
    clearTimeout(searchTimeout);
    if (!value) {
        taskResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        const response = await fetch(`/tasks/search?search=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        });
        taskResults.value = await response.json();
    }, 250);
}

function selectTask(task) {
    selectedTask.value = task;
    taskResults.value = [];
    taskSearch.value = '';
}

function submit() {
    form.transform((data) => ({ ...data, position: props.position, task_id: selectedTask.value?.id ?? null }))
        .post(`/boards/${props.boardId}/tiles`, { onSuccess: () => (isOpen.value = false) });
}

function clearTile() {
    router.delete(`/boards/${props.boardId}/tiles/${props.tile.id}`, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
    });
}
</script>
