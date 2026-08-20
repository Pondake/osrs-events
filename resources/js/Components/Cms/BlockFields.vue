<template>
    <div class="space-y-3">
        <template v-for="field in fields" :key="field.key">
            <!-- Repeaters (links, feature cards) recurse into this same
                 component for their sub-fields, so a nested shape needs no
                 second implementation. -->
            <div v-if="field.type === 'repeater'" class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-muted">{{ $t(field.label) }}</span>
                    <u-button
                        size="xs"
                        variant="ghost"
                        color="neutral"
                        icon="i-lucide-plus"
                        :disabled="(model[field.key]?.length ?? 0) >= field.max"
                        :label="$t('cms.add_item')"
                        @click="addItem(field)"
                    />
                </div>

                <div
                    v-for="(item, index) in model[field.key] ?? []"
                    :key="index"
                    class="rounded-md ring ring-default p-3 space-y-2"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-dimmed">{{ index + 1 }}</span>
                        <div class="flex items-center gap-1">
                            <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-chevron-up" :disabled="index === 0" :aria-label="$t('cms.move_up')" @click="moveItem(field, index, -1)" />
                            <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-chevron-down" :disabled="index === model[field.key].length - 1" :aria-label="$t('cms.move_down')" @click="moveItem(field, index, 1)" />
                            <u-button size="xs" variant="ghost" color="error" icon="i-lucide-trash-2" :aria-label="$t('cms.remove_item')" @click="removeItem(field, index)" />
                        </div>
                    </div>

                    <block-fields :fields="field.fields" :model="item" />
                </div>
            </div>

            <u-form-field v-else :label="$t(field.label)" :description="field.hint ? $t(field.hint) : undefined">
                <u-textarea
                    v-if="field.type === 'textarea'"
                    :model-value="model[field.key] ?? ''"
                    :rows="field.rows ?? 2"
                    class="w-full"
                    @update:model-value="set(field.key, $event)"
                />
                <u-select
                    v-else-if="field.type === 'select' || field.type === 'number-select'"
                    :model-value="model[field.key] ?? undefined"
                    :items="optionsFor(field)"
                    class="w-full"
                    @update:model-value="set(field.key, $event)"
                />
                <u-select
                    v-else-if="field.type === 'color'"
                    :model-value="model[field.key] ?? undefined"
                    :items="colorOptions"
                    class="w-full"
                    @update:model-value="set(field.key, $event)"
                />
                <icon-picker
                    v-else-if="field.type === 'icon'"
                    :model-value="model[field.key]"
                    @update:model-value="set(field.key, $event)"
                />
                <u-input
                    v-else
                    :model-value="model[field.key] ?? ''"
                    class="w-full"
                    @update:model-value="set(field.key, $event)"
                />
            </u-form-field>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import IconPicker from './IconPicker.vue';
import { BLOCK_COLORS } from './blocks';

const props = defineProps({
    fields: { type: Array, required: true },
    // Mutated in place rather than emitting: the block document is one
    // object the editor owns end to end, and threading update events up
    // through arbitrarily nested repeaters would be a lot of plumbing for
    // no extra safety — nothing else holds a reference to it.
    model: { type: Object, required: true },
});

const colorOptions = computed(() => BLOCK_COLORS.map((value) => ({ label: value, value })));

function optionsFor(field) {
    return (field.options ?? []).map((value) => ({ label: String(value), value }));
}

// Empty string back to null so a cleared field stores as absent rather than
// as an empty string — the renderer's coercers treat the two differently.
function set(key, value) {
    props.model[key] = value === '' ? null : value;
}

function addItem(field) {
    if (!Array.isArray(props.model[field.key])) props.model[field.key] = [];
    if (props.model[field.key].length >= field.max) return;

    const item = {};
    for (const sub of field.fields) item[sub.key] = null;
    props.model[field.key].push(item);
}

function removeItem(field, index) {
    props.model[field.key].splice(index, 1);
}

function moveItem(field, index, delta) {
    const list = props.model[field.key];
    const target = index + delta;
    if (target < 0 || target >= list.length) return;

    [list[index], list[target]] = [list[target], list[index]];
}
</script>
