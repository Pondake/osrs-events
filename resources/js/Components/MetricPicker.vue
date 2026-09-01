<template>
    <!-- u-select-menu, not u-select: it ships a search box in the popover
         (searchInput defaults on), which is the whole point. A plain
         u-select made you scroll 24 skills or 70 bosses looking for one you
         already knew the name of. `by="value"` so the selected item is
         matched on its metric rather than by object identity, which a fresh
         computed array breaks on every render. -->
    <u-select-menu
        :model-value="selected"
        :items="items"
        by="value"
        value-key="value"
        :placeholder="placeholder ?? $t(kind === 'boss' ? 'events.metric_pick_boss' : 'events.metric_pick_skill')"
        :search-input="{ placeholder: $t('common.search'), icon: 'i-lucide-search' }"
        class="w-full"
        @update:model-value="(value) => emit('update:modelValue', value ?? null)"
    >
        <!-- The trigger shows the icon too, not just the list. Without this
             the picker looks illustrated only while it is open. -->
        <template #leading>
            <img v-if="selectedIcon" :src="selectedIcon" alt="" class="size-4 object-contain" />
        </template>

        <template #item-leading="{ item }">
            <!-- Fixed box whether or not there is an icon, so a list where
                 only some entries have one does not stagger left and right.
                 Bosses have no icon set yet — see the note in
                 scripts/extract-osrs-icons.mjs. -->
            <span class="flex size-5 items-center justify-center shrink-0">
                <img v-if="item.icon_url" :src="item.icon_url" alt="" class="size-4 object-contain" />
            </span>
        </template>
    </u-select-menu>
</template>

<script setup>
import { computed } from 'vue';
import { useMetricIcon } from '@/Composables/useMetricIcon';
import { trans } from 'laravel-vue-i18n';

/**
 * Picks the skill or boss an event races on.
 *
 * One component for both because the only thing that differs is which list
 * and which i18n namespace — and getting that pairing wrong is how a boss
 * slug ends up rendered as a missing skill key.
 */
const props = defineProps({
    modelValue: { type: String, default: null },
    // 'skill' | 'boss' | null. Null means the event type has no metric, and
    // the parent should not be rendering this at all.
    kind: { type: String, default: null },
    // The raw metric names for this kind, as shared by HandleInertiaRequests.
    metrics: { type: Array, default: () => [] },
    placeholder: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

// Through the composable rather than the bare helper, so an admin's override
// for a boss shows up in the picker too — see Composables/useMetricIcon.js.
const iconFor = useMetricIcon();

const items = computed(() => props.metrics.map((metric) => ({
    value: metric,
    label: trans(`${props.kind === 'boss' ? 'bosses' : 'skills'}.${metric}`),
    icon_url: iconFor(metric, props.kind),
})));

const selected = computed(() => props.modelValue ?? undefined);

const selectedIcon = computed(() => iconFor(props.modelValue, props.kind));
</script>
