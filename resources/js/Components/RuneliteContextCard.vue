<template>
    <div v-if="context" class="rounded-lg ring ring-default px-3 py-2 space-y-1.5 text-sm">
        <p v-if="context.npcName" class="flex items-center gap-1.5">
            <u-icon name="i-lucide-skull" class="size-3.5 text-muted shrink-0" />
            <span>{{ npcLabel }}</span>
        </p>

        <p v-if="context.killCount !== null && context.killCount !== undefined" class="flex items-center gap-1.5 text-muted">
            <u-icon name="i-lucide-hash" class="size-3.5 shrink-0" />
            {{ $t('board.runelite_context_kc', { n: context.killCount }) }}
        </p>

        <p v-if="context.source === 'collection_log'" class="flex items-center gap-1.5 text-muted">
            <u-icon name="i-lucide-book-marked" class="size-3.5 shrink-0" />
            {{ $t('board.runelite_context_collection_log') }}
        </p>

        <div v-if="context.items?.length" class="flex flex-wrap gap-1 pt-0.5">
            <u-badge
                v-for="(item, i) in context.items"
                :key="i"
                color="neutral"
                variant="subtle"
                size="sm"
                :label="itemLabel(item)"
            />
        </div>

        <p v-if="context.occurredAt" class="text-xs text-muted">
            {{ $t('board.runelite_context_at', { when: occurredAt }) }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

/**
 * What the plugin actually saw for a RUNELITE claim — under the "no
 * screenshot" line, not instead of it. Null renders nothing: a claim that
 * predates this field, or a manual one, has no context to show.
 */
const props = defineProps({
    context: { type: Object, default: null },
});

const npcLabel = computed(() => {
    if (!props.context?.npcName) return '';

    return props.context.npcLevel
        ? trans('board.runelite_context_npc_level', { name: props.context.npcName, level: props.context.npcLevel })
        : trans('board.runelite_context_npc', { name: props.context.npcName });
});

const occurredAt = computed(() => (
    props.context?.occurredAt ? new Date(props.context.occurredAt).toLocaleString() : ''
));

function itemLabel(item) {
    return item.quantity > 1 ? `${item.quantity}× ${item.name}` : item.name;
}
</script>
