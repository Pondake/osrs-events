<template>
    <div class="space-y-2">
        <ol class="space-y-2">
            <li
                v-for="(row, index) in modelValue"
                :key="row.key"
                :draggable="armed === index"
                class="flex items-start gap-2 rounded-lg transition-colors"
                :class="over === index && dragging !== index ? 'bg-primary/5 ring-1 ring-primary/40' : ''"
                @dragstart="onDragStart(index, $event)"
                @dragover.prevent="over = index"
                @dragleave="over === index && (over = null)"
                @drop.prevent="onDrop(index)"
                @dragend="reset"
            >
                <!-- The handle is the only part that starts a drag, so the
                     text box inside stays selectable. Hidden on touch, where
                     native drag does not exist; the arrows cover it there. -->
                <span
                    v-if="modelValue.length > 1"
                    class="hidden sm:flex items-center justify-center w-6 shrink-0 text-muted cursor-grab active:cursor-grabbing"
                    :class="rowHeight"
                    :title="$t('profile.osrs_character_drag')"
                    @pointerdown="armed = index"
                    @pointerup="armed = null"
                >
                    <u-icon name="i-lucide-grip-vertical" class="size-4" />
                </span>

                <div class="flex-1 min-w-0">
                    <osrs-username-field
                        :model-value="row.username"
                        :error="errors[`characters.${index}`] ?? null"
                        :size="size"
                        :placeholder="index === 0 ? $t('profile.osrs_main_placeholder') : $t('profile.osrs_alt_placeholder')"
                        :label="index === 0 ? $t('auth.field_osrs_username') : $t('profile.osrs_alt_label', { n: index })"
                        @update:model-value="(value) => update(index, value)"
                    />
                    <p v-if="errors[`characters.${index}`]" class="text-xs text-error mt-1">{{ errors[`characters.${index}`] }}</p>
                </div>

                <div class="flex items-center gap-1 shrink-0" :class="rowHeight">
                    <u-badge
                        v-if="index === 0"
                        color="primary"
                        variant="subtle"
                        size="sm"
                        :label="$t('profile.osrs_main')"
                    />
                    <u-badge
                        v-else
                        color="neutral"
                        variant="subtle"
                        size="sm"
                        :label="$t('profile.osrs_alt')"
                    />
                    <!-- Client-only: a tooltip is an interactive Nuxt UI
                         component, and this list renders on an SSR page. -->
                    <client-only v-if="provenNames.includes(row.username.trim().toLowerCase())">
                        <u-tooltip :text="$t('profile.osrs_character_proven')">
                            <span tabindex="0" class="inline-flex rounded focus-visible:outline-2 focus-visible:outline-primary" :aria-label="$t('profile.osrs_character_proven')">
                                <u-icon name="i-lucide-shield-check" class="size-4 text-success" />
                            </span>
                        </u-tooltip>
                        <template #fallback>
                            <u-icon name="i-lucide-shield-check" class="size-4 text-success" />
                        </template>
                    </client-only>
                    <u-button
                        v-if="index > 0"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-arrow-up"
                        :aria-label="$t('profile.osrs_character_up')"
                        :title="index === 1 ? $t('profile.osrs_character_make_main') : $t('profile.osrs_character_up')"
                        @click="move(index, index - 1)"
                    />
                    <u-button
                        v-if="modelValue.length > 1"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-x"
                        :aria-label="$t('profile.osrs_character_remove')"
                        :title="$t('profile.osrs_character_remove')"
                        @click="remove(index)"
                    />
                </div>
            </li>
        </ol>

        <p v-if="errors.characters" class="text-xs text-error">{{ errors.characters }}</p>

        <div class="flex items-center gap-3 flex-wrap">
            <u-button
                v-if="modelValue.length < max"
                size="sm"
                color="neutral"
                variant="outline"
                icon="i-lucide-plus"
                :label="$t('profile.osrs_add_alt')"
                @click="add"
            />
            <span class="text-xs text-muted">{{ $t('profile.osrs_character_count', { count: modelValue.length, max }) }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import OsrsUsernameField from '@/Components/OsrsUsernameField.vue';
import { characterRow } from '@/Support/osrsCharacters';

/**
 * The account's RuneScape names as an ordered list: the top one is the main,
 * the rest are alts. Settings and the first-run tour both use it and both
 * post the whole list (see ConnectionsController::saveCharacters).
 *
 * The model is `[{ key, username }]`, built with Support/osrsCharacters —
 * the key keeps a row's field (and its blur-check verdict) attached to it
 * while the list is reordered.
 */
const props = defineProps({
    modelValue: { type: Array, required: true },
    max: { type: Number, default: 5 },
    // The form's errors object: `characters` for the list, `characters.N` per row.
    errors: { type: Object, default: () => ({}) },
    // Names a RuneLite client has proved, for the shield next to them.
    proven: { type: Array, default: () => [] },
    size: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue']);

// The input's own height, so the handle and the controls beside it line up
// with it rather than with the tallest size.
const rowHeight = computed(() => (props.size === 'sm' ? 'h-7' : 'h-8'));

const provenNames = computed(() => props.proven.map((name) => name.trim().toLowerCase()));

function update(index, username) {
    emit('update:modelValue', props.modelValue.map((row, i) => (i === index ? { ...row, username } : row)));
}

function add() {
    emit('update:modelValue', [...props.modelValue, characterRow()]);
}

function remove(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
}

function move(from, to) {
    if (from === to || to < 0 || to >= props.modelValue.length) return;

    const rows = [...props.modelValue];
    const [row] = rows.splice(from, 1);
    rows.splice(to, 0, row);
    emit('update:modelValue', rows);
}

const armed = ref(null);
const dragging = ref(null);
const over = ref(null);

function onDragStart(index, event) {
    dragging.value = index;
    event.dataTransfer.effectAllowed = 'move';
    // Firefox starts no drag without data.
    event.dataTransfer.setData('text/plain', String(index));
}

function onDrop(index) {
    if (dragging.value !== null) move(dragging.value, index);
    reset();
}

function reset() {
    armed.value = null;
    dragging.value = null;
    over.value = null;
}
</script>
