<template>
    <u-modal v-model:open="isOpen" :title="$t('bingo.edit_square')">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('tile_editor.task')" :description="$t('tile_editor.task_desc')">
                    <div class="space-y-2">
                        <div v-if="selectedTask" class="flex items-center gap-3 rounded-md ring ring-default px-3 py-2">
                            <img v-if="selectedTask.icon_url" :src="selectedTask.icon_url" alt="" class="size-6 object-contain" />
                            <span class="flex-1 min-w-0 truncate text-sm">{{ selectedTask.title }}</span>
                            <u-button
                                icon="i-lucide-x"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                :aria-label="$t('common.clear')"
                                @click="selectedTask = null"
                            />
                        </div>

                        <u-input
                            v-else
                            v-model="taskSearch"
                            icon="i-lucide-search"
                            :placeholder="$t('common.search')"
                            class="w-full"
                            @update:model-value="debouncedSearch"
                        />

                        <div v-if="taskResults.length" class="rounded-md ring ring-default divide-y divide-default max-h-56 overflow-y-auto">
                            <button
                                v-for="task in taskResults"
                                :key="task.id"
                                type="button"
                                class="w-full flex items-center gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left"
                                @click="selectTask(task)"
                            >
                                <img v-if="task.icon_url" :src="task.icon_url" alt="" class="size-5 object-contain" />
                                <span class="text-sm truncate">{{ task.title }}</span>
                            </button>
                        </div>
                    </div>
                </u-form-field>

                <u-form-field :label="$t('tile_editor.title_override')" :description="$t('tile_editor.title_override_desc')">
                    <u-input v-model="form.title_override" class="w-full" />
                </u-form-field>

                <!-- Tile weighting. Counting squares treats a Zulrah pet and
                     a bucket of sand as equal, which is not how these events
                     are actually scored. -->
                <u-form-field :label="$t('bingo.points_field')" :description="$t('bingo.points_desc')">
                    <u-input v-model.number="form.points" type="number" min="0" max="1000" class="w-full" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :loading="form.processing" :label="$t('common.save')" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

/**
 * Sets what one bingo square asks for.
 *
 * Deliberately a separate component from TileEditModal rather than a shared
 * one with branches: a Snakes & Ladders tile also carries a type
 * (snake/ladder) and a target position, and none of that means anything on a
 * bingo card. Two small forms beat one form that hides half of itself.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    square: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const form = useForm({ title_override: '', points: 1 });
const selectedTask = ref(null);
const taskSearch = ref('');
const taskResults = ref([]);

watch(
    () => props.square,
    (square) => {
        form.title_override = square?.titleOverride ?? '';
        form.points = square?.points ?? 1;
        selectedTask.value = square?.task ?? null;
        taskSearch.value = '';
        taskResults.value = [];
    },
    { immediate: true },
);

// Debounced for the same reason the tile editor's is: this fires per
// keystroke against a real endpoint.
let searchTimeout;
function debouncedSearch(value) {
    clearTimeout(searchTimeout);

    if (!value) {
        taskResults.value = [];

        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/tasks/search?search=${encodeURIComponent(value)}`, {
                headers: { Accept: 'application/json' },
            });
            taskResults.value = await response.json();
        } catch (error) {
            console.error(error);
        }
    }, 250);
}

function selectTask(task) {
    selectedTask.value = task;
    taskResults.value = [];
    taskSearch.value = '';
}

function submit() {
    form.transform((data) => ({ ...data, task_id: selectedTask.value?.id ?? null }))
        .patch(`/events/${props.eventId}/bingo/squares/${props.square.id}`, {
            preserveScroll: true,
            onSuccess: () => (isOpen.value = false),
        });
}
</script>
