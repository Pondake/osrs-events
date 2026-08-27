<template>
    <!-- A popover confirm, not window.confirm() or a full modal — the action
         being confirmed is a single button click, so the confirmation should
         stay anchored to it rather than taking over the whole screen. -->
    <u-popover v-model:open="open" :ui="{ content: 'p-4 w-72' }" @update:open="(v) => !v && (note = '')">
        <slot />

        <template #content>
            <div class="space-y-3">
                <p class="text-sm text-highlighted">{{ message }}</p>

                <u-textarea
                    v-if="notePlaceholder"
                    v-model="note"
                    :placeholder="notePlaceholder"
                    :rows="2"
                    class="w-full"
                    size="xs"
                />

                <slot name="extra" />

                <div class="flex justify-end gap-2">
                    <u-button
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        :label="$t('common.cancel')"
                        @click="open = false"
                    />
                    <u-button
                        size="xs"
                        :color="color"
                        :label="confirmLabel"
                        :loading="loading"
                        @click="confirm"
                    />
                </div>
            </div>
        </template>
    </u-popover>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    message: { type: String, required: true },
    confirmLabel: { type: String, required: true },
    color: { type: String, default: 'error' },
    loading: { type: Boolean, default: false },
    // When set, an optional note textarea renders above the buttons and its
    // value is passed as the confirm event's second argument. Left unset,
    // there is no note field at all — most confirms here don't need one.
    notePlaceholder: { type: String, default: null },
});

const emit = defineEmits(['confirm']);

const open = ref(false);
const note = ref('');

function confirm() {
    emit('confirm', note.value, () => {
        // Passed to the caller so the popover only closes once the action
        // actually finishes — a router.delete() is async, and closing
        // immediately would let a second click queue a second request while
        // the first is still in flight.
        open.value = false;
        note.value = '';
    });
}

defineExpose({ close: () => { open.value = false; } });
</script>
