<template>
    <div v-if="entries.length" class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
        <div v-for="entry in entries" :key="entry.key" class="flex items-center gap-1.5 text-xs">
            <span class="text-muted">{{ entry.label }}</span>

            <!-- from → to for settings diffs; a bare value otherwise. -->
            <template v-if="entry.from">
                <span :class="valueClass(entry.from)">
                    <u-icon v-if="entry.from.kind === 'bool'" :name="boolIcon(entry.from.value)" class="size-3.5 shrink-0" :class="boolClass(entry.from.value)" :aria-label="boolLabel(entry.from.value)" />
                    <template v-else>{{ display(entry.from) }}</template>
                </span>
                <u-icon name="i-lucide-arrow-right" class="size-3 text-dimmed shrink-0" />
            </template>

            <span :class="valueClass(entry.to)">
                <u-icon v-if="entry.to.kind === 'bool'" :name="boolIcon(entry.to.value)" class="size-3.5 shrink-0" :class="boolClass(entry.to.value)" :aria-label="boolLabel(entry.to.value)" />
                <template v-else>{{ display(entry.to) }}</template>
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { metadataEntries } from '@/Support/audit';

const props = defineProps({
    metadata: { type: Object, default: null },
});

const entries = computed(() => metadataEntries(props.metadata));

/**
 * Booleans show as a coloured icon rather than the words "true"/"false" —
 * those are a storage detail, not something to put in front of an admin.
 * The icon carries an aria-label because neither colour nor glyph states
 * which value it is on its own.
 */
function boolIcon(value) {
    return value ? 'i-lucide-circle-check' : 'i-lucide-circle-x';
}

function boolClass(value) {
    return value ? 'text-success' : 'text-error';
}

function boolLabel(value) {
    return trans(value ? 'common.enabled' : 'common.disabled');
}

function valueClass(value) {
    return value.kind === 'empty' ? 'text-dimmed' : 'text-highlighted';
}

function display(value) {
    return value.kind === 'empty' ? '—' : value.value;
}
</script>
