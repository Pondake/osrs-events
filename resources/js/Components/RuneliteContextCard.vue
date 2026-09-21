<template>
    <div v-if="context || submittedBy || noProofNotice" class="rounded-lg ring ring-default px-3 py-2 space-y-1.5 text-sm">
        <!-- Who is asking, folded in here rather than left in a separate box
             above it and a "no screenshot" alert above that — a RuneLite
             claim's identity, source and detection detail used to be three
             stacked reads for one fact. Omitted when nobody passed a name:
             the player-facing claim dialogs don't need it (it's always the
             viewer's own claim), so they stay exactly as compact as before. -->
        <div v-if="submittedBy" class="flex items-center gap-2 pb-1.5 border-b border-default">
            <u-avatar :src="submittedByAvatar ?? undefined" :alt="submittedBy" size="xs" />
            <div class="min-w-0">
                <p class="truncate">{{ submittedBy }}</p>
                <p v-if="submittedByOsrs" class="text-xs text-muted truncate flex items-center gap-1">
                    <u-icon name="i-lucide-user-round" class="size-3" />
                    {{ submittedByOsrs }}
                </p>
            </div>
        </div>

        <p v-if="noProofNotice" class="flex items-center gap-1.5 text-muted">
            <u-icon name="i-lucide-puzzle" class="size-3.5 shrink-0" />
            {{ $t('board.runelite_no_proof_desc') }}
        </p>

        <p
            v-for="code in context?.doubts ?? []"
            :key="code"
            class="flex items-start gap-1.5 text-warning"
        >
            <u-icon name="i-lucide-triangle-alert" class="size-3.5 shrink-0 mt-0.5" />
            {{ $t(`board.runelite_doubt_${code}`) }}
        </p>

        <p v-if="context?.npcName" class="flex items-center gap-1.5">
            <u-icon name="i-lucide-skull" class="size-3.5 text-muted shrink-0" />
            <span>{{ npcLabel }}</span>
        </p>

        <p v-if="context?.killCount !== null && context?.killCount !== undefined" class="flex items-center gap-1.5 text-muted">
            <u-icon name="i-lucide-hash" class="size-3.5 shrink-0" />
            {{ $t('board.runelite_context_kc', { n: context.killCount }) }}
        </p>

        <p v-if="context?.source === 'collection_log'" class="flex items-center gap-1.5 text-muted">
            <u-icon name="i-lucide-book-marked" class="size-3.5 shrink-0" />
            {{ $t('board.runelite_context_collection_log') }}
        </p>

        <div v-if="context?.items?.length" class="flex flex-wrap gap-1 pt-0.5">
            <u-badge
                v-for="(item, i) in context.items"
                :key="i"
                color="neutral"
                variant="subtle"
                size="sm"
                :label="itemLabel(item)"
            />
        </div>

        <p v-if="context?.occurredAt" class="text-xs text-muted">
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
 *
 * `submittedBy`/`noProofNotice` are optional and only passed by the review
 * modals — see BingoReviewModal.vue and TileReviewModal.vue for why: they
 * used to render the claimant identity and the "no screenshot" line as
 * separate boxes above this one.
 */
const props = defineProps({
    context: { type: Object, default: null },
    submittedBy: { type: String, default: null },
    submittedByAvatar: { type: String, default: null },
    submittedByOsrs: { type: String, default: null },
    noProofNotice: { type: Boolean, default: false },
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
