<template>
    <div class="space-y-4">
        <div>
            <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">{{ $t('board.detail_holders') }}</p>

            <div v-if="loading" class="space-y-2">
                <u-skeleton v-for="i in 2" :key="i" class="h-8 w-full" />
            </div>

            <p v-else-if="!holders.length" class="text-sm text-muted">{{ $t('board.detail_nobody') }}</p>

            <ul v-else class="space-y-1.5">
                <li v-for="(row, i) in holders" :key="i" class="flex items-center gap-2 text-sm">
                    <u-avatar
                        :src="row.avatarUrl ?? undefined"
                        :alt="row.name ?? undefined"
                        :icon="row.name === null ? 'i-lucide-user' : undefined"
                        size="2xs"
                        class="shrink-0"
                    />
                    <!-- A null name is not a missing one: on a listed
                         invite-only event the field is public and the roster
                         is not, so the row is deliberately faceless. See
                         BoardAccessService::canSeeParticipants(). -->
                    <span class="truncate" :class="row.name === null ? 'text-muted italic' : ''">
                        {{ row.name ?? $t('events.anonymous_player') }}
                    </span>
                    <span v-if="row.isYou" class="text-xs text-primary font-medium shrink-0">{{ $t('board.detail_you') }}</span>
                    <!-- A claim still in the queue is not a claim that
                         landed. Saying so here keeps the list honest about
                         who actually has the square. -->
                    <u-badge
                        v-if="row.status === 'PENDING'"
                        color="warning"
                        variant="subtle"
                        size="sm"
                        :label="$t('board.detail_pending')"
                        class="shrink-0"
                    />
                    <claim-source-badge v-if="row.via === 'RUNELITE'" :via="row.via" class="shrink-0 ms-auto" />
                </li>
            </ul>
        </div>

        <!-- Only a counted target has an "on the way": on a square claimed by
             the first report there is no middle to be in. -->
        <div v-if="requiredCount > 1 && (loading || inProgress.length)">
            <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">{{ $t('board.detail_in_progress') }}</p>

            <div v-if="loading" class="space-y-2">
                <u-skeleton class="h-8 w-full" />
            </div>

            <ul v-else class="space-y-1.5">
                <li v-for="(row, i) in inProgress" :key="i" class="flex items-center gap-2 text-sm">
                    <u-avatar
                        :src="row.avatarUrl ?? undefined"
                        :alt="row.name ?? undefined"
                        :icon="row.name === null ? 'i-lucide-user' : undefined"
                        size="2xs"
                        class="shrink-0"
                    />
                    <span class="truncate" :class="row.name === null ? 'text-muted italic' : ''">
                        {{ row.name ?? $t('events.anonymous_player') }}
                    </span>
                    <span v-if="row.isYou" class="text-xs text-primary font-medium shrink-0">{{ $t('board.detail_you') }}</span>

                    <!-- The same rail the square carries, at a size that can
                         afford to be read: a row of numbers with no shape to
                         them is a table, and the question here is who is
                         close. -->
                    <span class="ms-auto flex items-center gap-2 shrink-0">
                        <span class="hidden sm:block w-16 h-1.5 rounded-full bg-elevated overflow-hidden">
                            <span class="block h-full bg-primary/60" :style="{ width: `${percent(row.done)}%` }" />
                        </span>
                        <span class="tabular-nums text-xs text-muted">
                            {{ $t('common.progress_badge', { done: row.done, total: requiredCount }) }}
                        </span>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import ClaimSourceBadge from '@/Components/ClaimSourceBadge.vue';

/**
 * Who has this square or tile, and who is on the way to it.
 *
 * The board has room for three faces and your own count; this is the rest of
 * the answer, and it is the same answer on both board types — see
 * TargetDetailService, which builds both.
 */
const props = defineProps({
    holders: { type: Array, default: () => [] },
    inProgress: { type: Array, default: () => [] },
    requiredCount: { type: Number, default: 1 },
    // The detail is fetched when the dialog opens, so the first paint has
    // nothing yet — see the `squareDetail` / `tileDetail` props.
    loading: { type: Boolean, default: false },
});

function percent(done) {
    return Math.max(0, Math.min(100, (done / props.requiredCount) * 100));
}
</script>
