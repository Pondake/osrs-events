<template>
    <!-- The race analogue of BoardPreview / BingoPreview — same slot on
         Boards/Mine.vue, same footprint. A race has no grid to draw, so this
         shows the one thing it actually has: where the viewer stands. Kept
         `aspect-square` like the other two so all three previews read as
         siblings of one family rather than one oddly-shaped row among
         square ones. -->
    <div class="aspect-square rounded-lg ring ring-default bg-default p-4 flex flex-col items-center justify-center gap-1 text-center">
        <template v-if="standing?.rank">
            <span class="text-4xl font-bold text-primary tabular-nums leading-none">#{{ standing.rank }}</span>
            <span class="text-xs text-muted">{{ $t('events.of_participants', { count: standing.participants }) }}</span>
            <span v-if="gainedLabel" class="mt-2 text-sm font-medium text-highlighted tabular-nums">+{{ gainedLabel }}</span>
        </template>

        <!-- A sync failure (name clash, untracked account) still means
             something to show — the same words the standings table already
             uses for it, not a second vocabulary for the same fact. -->
        <template v-else-if="standing?.error">
            <u-icon name="i-lucide-circle-help" class="size-8 text-muted" />
            <span class="text-xs text-muted">{{ $t(`events.error_${standing.error}`) }}</span>
        </template>

        <!-- Entered, but Wise Old Man hasn't answered for this account yet. -->
        <template v-else-if="standing">
            <u-icon name="i-lucide-hourglass" class="size-8 text-muted" />
            <span class="text-xs text-muted">{{ $t('events.pending_sync') }}</span>
        </template>

        <!-- No standing at all: hosting this race without having entered
             it — a real, common state (BoardShow's own sidebar has the same
             "no board of your own yet" case for a Snakes & Ladders host).
             Distinct from "pending sync": there is nothing to sync because
             there is no entry, and saying so avoids implying a wait that
             will never resolve on its own. -->
        <template v-else>
            <u-icon name="i-lucide-trophy" class="size-8 text-dimmed" />
            <span class="text-xs text-muted">{{ $t('events.race_not_entered') }}</span>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatMetricValue } from '@/Support/metrics';

const props = defineProps({
    // { rank, participants, gained, syncedAt, error } from
    // BoardController::mine(), or null when the viewer hosts this race
    // without having entered it.
    standing: { type: Object, default: null },
});

const gainedLabel = computed(() => (
    props.standing?.syncedAt && !props.standing?.error && props.standing?.gained != null
        ? formatMetricValue(props.standing.gained)
        : null
));
</script>
