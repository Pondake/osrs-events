<template>
    <u-modal v-model:open="isOpen" :title="$t('bingo.edit_square')" :dismissible="false">
        <template #body>
            <div class="space-y-4 py-2">
                <!-- The free square. Above the task picker because it
                     changes what the rest of the form is for: a wildcard
                     needs no task and cannot be claimed, so asking what it
                     asks for first would be asking the wrong question. -->
                <u-form-field :description="$t('bingo.wildcard_desc')">
                    <u-switch v-model="form.is_wildcard" :label="$t('bingo.wildcard_field')" />
                </u-form-field>

                <!-- Two questions, two sections: what the square asks for,
                     and how what arrives gets counted. They were one flat
                     list, and a host reading it could not tell which number
                     meant what. More counting fields are expected, so the
                     second section is the place they land. -->
                <p class="text-xs font-medium text-muted uppercase tracking-wide pt-2 border-t border-default">
                    {{ $t('tile_editor.section_task') }}
                </p>

                <u-form-field
                    v-if="!form.is_wildcard"
                    :label="$t('tile_editor.task')"
                    :description="$t('tile_editor.task_desc')"
                >
                    <task-picker v-model="selectedTask" :event-id="eventId" />
                </u-form-field>

                <u-form-field :label="$t('tile_editor.title_override')" :description="$t('tile_editor.title_override_desc')">
                    <u-input v-model="form.title_override" class="w-full" :placeholder="selectedTask?.title ?? ''" />
                </u-form-field>

                <!-- Tile weighting. Counting squares treats a Zulrah pet and
                     a bucket of sand as equal, which is not how these events
                     are actually scored. -->
                <u-form-field :label="$t('bingo.points_field')" :description="$t('bingo.points_desc')">
                    <u-input v-model.number="form.points" type="number" min="0" max="1000" class="w-full" />
                </u-form-field>

                <template v-if="!form.is_wildcard">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide pt-2 border-t border-default">
                        {{ $t('tile_editor.section_counting') }}
                    </p>

                    <!-- "Do this N times." The other counting mode, and the
                         one a Tempoross square set to 3 actually meant: three
                         separate kills, not one drop of three. First, because
                         it is the question a host asks first. -->
                    <u-form-field
                        :label="$t('tile_editor.required_count')"
                        :description="$t('tile_editor.required_count_desc')"
                        :error="form.errors.required_count"
                    >
                        <u-input v-model.number="form.required_count" type="number" min="1" max="1000" class="w-full" />
                    </u-form-field>

                    <!-- "Only counts from N." A clan that had agreed only
                         the 25-stack Soaked page counted could not say so
                         anywhere, so every single page claimed the square. -->
                    <u-form-field
                        :label="$t('tile_editor.min_quantity')"
                        :description="$t('tile_editor.min_quantity_desc')"
                        :error="form.errors.min_quantity"
                    >
                        <u-input v-model.number="form.min_quantity" type="number" min="1" class="w-full" />
                    </u-form-field>
                </template>
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
import TaskPicker from '@/Components/TaskPicker.vue';

/**
 * Sets what one bingo square asks for.
 *
 * Deliberately a separate component from TileEditModal rather than a shared
 * one with branches: a Snakes & Ladders tile also carries a type
 * (snake/ladder) and a target position, and none of that means anything on a
 * bingo card. Two small forms beat one form that hides half of itself. What
 * the two genuinely do share — finding the thing a square asks for — is
 * TaskPicker, which both now use.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    square: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const form = useForm({ title_override: '', points: 1, min_quantity: 1, required_count: 1, is_wildcard: false });
const selectedTask = ref(null);

watch(
    () => props.square,
    (square) => {
        form.title_override = square?.titleOverride ?? '';
        form.points = square?.points ?? 1;
        form.min_quantity = square?.minQuantity ?? 1;
        form.required_count = square?.requiredCount ?? 1;
        form.is_wildcard = square?.isWildcard ?? false;
        selectedTask.value = square?.task ?? null;
    },
    { immediate: true },
);

function submit() {
    // A wildcard drops its task: it is not asking for anything, and leaving
    // one attached would render a claimable-looking square that isn't.
    // A cleared number field is '' rather than 1, and a wildcard asks for
    // nothing so it can hold no threshold either — same reason its task goes.
    form.transform((data) => ({
        ...data,
        task_id: data.is_wildcard ? null : (selectedTask.value?.id ?? null),
        min_quantity: data.is_wildcard ? 1 : (Number(data.min_quantity) || 1),
        required_count: data.is_wildcard ? 1 : (Number(data.required_count) || 1),
    }))
        .patch(`/events/${props.eventId}/bingo/squares/${props.square.id}`, {
            preserveScroll: true,
            onSuccess: () => (isOpen.value = false),
        });
}
</script>
