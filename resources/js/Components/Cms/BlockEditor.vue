<template>
    <div class="space-y-3">
        <div
            v-for="(block, index) in blocks"
            :key="block._key"
            class="rounded-lg ring bg-default"
            :class="expanded === block._key ? 'ring-primary' : 'ring-default'"
        >
            <div class="flex items-center gap-2 px-3 py-2">
                <u-icon :name="iconFor(block.type)" class="size-4 shrink-0 text-muted" />
                <button type="button" class="flex-1 min-w-0 text-left text-sm" @click="toggle(block._key)">
                    <span class="font-medium">{{ labelFor(block.type) }}</span>
                    <!-- The first bit of the block's own copy, so a collapsed
                         list is readable as content rather than as eight rows
                         all saying "Section". -->
                    <span v-if="summarise(block)" class="text-muted"> — {{ summarise(block) }}</span>
                </button>

                <div class="flex items-center gap-1 shrink-0">
                    <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-chevron-up" :disabled="index === 0" :aria-label="$t('cms.move_up')" @click="move(index, -1)" />
                    <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-chevron-down" :disabled="index === blocks.length - 1" :aria-label="$t('cms.move_down')" @click="move(index, 1)" />
                    <u-button size="xs" variant="ghost" color="error" icon="i-lucide-trash-2" :aria-label="$t('cms.remove_block')" @click="remove(index)" />
                </div>
            </div>

            <div v-if="expanded === block._key" class="border-t border-default p-3 space-y-4">
                <block-fields v-if="fieldsFor(block.type).length" :fields="fieldsFor(block.type)" :model="block.props" />
                <p v-else class="text-xs text-muted">{{ $t('cms.no_settings') }}</p>

                <!-- Containers nest this same editor, which is what keeps a
                     section's children editable without a second UI. -->
                <div v-if="isContainer(block.type)" class="pt-2 border-t border-default">
                    <p class="text-xs font-medium text-muted mb-2">{{ $t('cms.nested_blocks') }}</p>
                    <block-editor v-model="block.blocks" :depth="depth + 1" />
                </div>
            </div>
        </div>

        <u-dropdown-menu v-if="depth < MAX_DEPTH" :items="[addItems]">
            <u-button
                size="sm"
                variant="subtle"
                color="neutral"
                icon="i-lucide-plus"
                :label="depth === 0 ? $t('cms.add_block') : $t('cms.add_nested_block')"
            />
        </u-dropdown-menu>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import BlockFields from './BlockFields.vue';
import { BLOCK_TYPES, blankBlock, blockTypeOptions, fieldsFor, isContainer } from './blocks';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue']);

// Mirrors PageRenderer's own guard, so the editor cannot author a document
// deeper than the renderer will draw.
const MAX_DEPTH = 3;

const expanded = ref(null);

/**
 * Stored blocks have no id, and array index is a poor :key — reordering or
 * deleting would make Vue reuse the wrong row's open/closed state. A
 * non-enumerable key is attached instead, so it survives editing but never
 * reaches the saved JSON (JSON.stringify skips non-enumerable properties).
 */
let nextKey = 0;
function keyed(block) {
    if (!Object.prototype.hasOwnProperty.call(block, '_key')) {
        Object.defineProperty(block, '_key', { value: `b${nextKey++}`, enumerable: false, writable: true });
    }

    return block;
}

const blocks = computed(() => props.modelValue.map(keyed));

const addItems = computed(() =>
    blockTypeOptions().map((option) => ({
        label: trans(option.label),
        icon: option.icon,
        onSelect: () => add(option.value),
    })),
);

function labelFor(type) {
    return BLOCK_TYPES[type] ? trans(BLOCK_TYPES[type].label) : type;
}

function iconFor(type) {
    return BLOCK_TYPES[type]?.icon ?? 'i-lucide-circle-dot';
}

// Copy fields, in the order a row is best identified by. Checked by name
// rather than taking the first string prop: that picked up enum-ish values
// and labelled a callout "warning" instead of by its title.
const SUMMARY_KEYS = ['title', 'text', 'label', 'description'];

/** Best available line of the block's own copy, so rows read as content. */
function summarise(block) {
    const props = block.props ?? {};
    const key = SUMMARY_KEYS.find((k) => typeof props[k] === 'string' && props[k].trim());
    const value = key ? props[key].trim() : null;

    if (!value) return null;

    return value.length > 48 ? `${value.slice(0, 48)}…` : value;
}

function update(next) {
    emit('update:modelValue', next);
}

function add(type) {
    const block = keyed(blankBlock(type));
    update([...props.modelValue, block]);
    expanded.value = block._key;
}

function remove(index) {
    const next = [...props.modelValue];
    next.splice(index, 1);
    update(next);
}

function move(index, delta) {
    const target = index + delta;
    if (target < 0 || target >= props.modelValue.length) return;

    const next = [...props.modelValue];
    [next[index], next[target]] = [next[target], next[index]];
    update(next);
}

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}
</script>
