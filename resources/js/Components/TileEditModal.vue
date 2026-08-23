<template>
    <u-modal v-model:open="isOpen" :title="`${$t('tile_editor.title')} ${position + 1}`" :dismissible="false">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('tile_editor.task_label')" :description="$t('tile_editor.task_desc')">
                    <task-picker v-model="selectedTask" :event-id="eventId" />
                </u-form-field>

                <u-form-field :label="$t('tile_editor.title_override')" :description="$t('tile_editor.title_override_desc')">
                    <u-input v-model="form.title_override" class="w-full" :placeholder="selectedTask?.title ?? ''" />
                </u-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <u-form-field :label="$t('tile_editor.tile_type')">
                        <u-select v-model="form.type" :items="typeOptions" class="w-full" />
                    </u-form-field>
                    <!-- The number printed on the tile, not the array index
                         behind it. This field was bound straight to
                         `target_position`, which is zero-based — so a host
                         typing 9 got an arrow to tile 10, and the tile list
                         editor two clicks away (which does speak in printed
                         numbers) disagreed with it about the same snake.
                         Reported as "the editor said target tile 9 while the
                         arrow pointed somewhere else". It did. -->
                    <u-form-field
                        v-if="form.type !== 'NORMAL'"
                        :label="$t('tile_editor.target_tile')"
                        :description="$t('tile_editor.target_tile_desc', { max: tileCount })"
                        :error="form.errors.target_position"
                    >
                        <u-input
                            v-model.number="targetTileNumber"
                            type="number"
                            min="1"
                            :max="tileCount"
                            class="w-full"
                        />
                    </u-form-field>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between gap-2 w-full">
                <u-button v-if="tile" color="error" variant="outline" :label="$t('tile_editor.clear_tile')" @click="clearTile" />
                <div class="flex gap-2 ml-auto">
                    <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                    <u-button color="primary" :label="$t('common.save')" :loading="form.processing" @click="submit" />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import TaskPicker from '@/Components/TaskPicker.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    // Named eventId, not boardId: it always WAS the event id — every caller
    // passes `board.id` from a page whose `board` prop is the event — and
    // the URLs below are /events/{id}/tiles. TaskPicker needs the same value
    // for its own event-scoped endpoints, so the misleading name got fixed
    // rather than propagated.
    eventId: { type: String, required: true },
    position: { type: Number, required: true },
    tile: { type: Object, default: null },
    // How many tiles the board has, so the target can be bounded to one that
    // exists rather than accepting any number and failing later.
    tileCount: { type: Number, default: 0 },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const typeOptions = [
    { label: trans('tile_editor.type_normal'), value: 'NORMAL' },
    { label: trans('tile_editor.type_snake_full'), value: 'SNAKE' },
    { label: trans('tile_editor.type_ladder_full'), value: 'LADDER' },
];

const selectedTask = ref(null);

/**
 * The board stores positions from zero and prints them from one. Everything
 * a host reads — the tile they clicked, the badge on the list, the arrow's
 * own label — is the printed number, so this field is too, and the
 * conversion happens in one place instead of in the reader's head.
 */
const targetTileNumber = computed({
    get: () => (form.target_position === null || form.target_position === undefined ? null : form.target_position + 1),
    set: (value) => {
        const n = Number(value);

        form.target_position = Number.isFinite(n) && n >= 1 ? n - 1 : null;
    },
});

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
    },
    { immediate: true },
);

function submit() {
    form.transform((data) => ({ ...data, position: props.position, task_id: selectedTask.value?.id ?? null }))
        .post(`/events/${props.eventId}/tiles`, { onSuccess: () => (isOpen.value = false) });
}

function clearTile() {
    router.delete(`/events/${props.eventId}/tiles/${props.tile.id}`, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
    });
}
</script>
