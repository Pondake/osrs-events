<template>
    <template v-if="claims.length">
        <div v-if="placement === 'inline'" class="hidden sm:flex items-center gap-3 flex-wrap rounded-lg bg-warning/10 ring-1 ring-warning/40 px-4 py-2.5" data-review-bar>
            <u-icon name="i-lucide-gavel" class="size-5 text-warning shrink-0" />
            <span class="font-semibold text-warning">{{ $tChoice('board.review_bar_waiting', claims.length, { count: claims.length }) }}</span>
            <client-only>
                <span v-if="oldest" class="text-sm text-muted">{{ $t('board.review_bar_oldest', { time: relativeTime(oldest) }) }}</span>
            </client-only>
            <span class="flex-1" />
            <u-button color="warning" size="sm" icon="i-lucide-gavel" :label="$t('board.review_bar_open')" @click="emit('open')" />
        </div>

        <!-- Along the bottom on a phone: the board and the standings are
             long, and a host checking claims between their own turns should
             not have to scroll back up to find the way in. Sticky rather than fixed,
             and placed last in the page content, so it stops above the footer
             instead of covering it. -->
        <div v-else class="sm:hidden sticky bottom-0 z-40 -mx-4 mt-6 px-3 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] bg-default/95 backdrop-blur border-t border-default">
            <div class="flex items-center gap-3 rounded-lg bg-warning/10 ring-1 ring-warning/40 pl-3 pr-1.5 py-1.5">
                <u-icon name="i-lucide-gavel" class="size-5 text-warning shrink-0" />
                <span class="flex-1 min-w-0 truncate text-sm font-semibold text-warning">{{ $tChoice('board.review_bar_waiting_short', claims.length, { count: claims.length }) }}</span>
                <u-button color="warning" size="sm" :label="$t('board.review_bar_open_short')" @click="emit('open')" />
            </div>
        </div>
    </template>
</template>

<script setup>
import { computed } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import { relativeTime } from '@/Support/board';

const props = defineProps({
    // The review queue as the page already has it: `[{ submittedAt, ... }]`.
    claims: { type: Array, default: () => [] },
    // `inline` under the page header from `sm` up, `sticky` along the bottom below it.
    placement: { type: String, default: 'inline' },
});

const emit = defineEmits(['open']);

const oldest = computed(() => props.claims
    .map((claim) => claim.submittedAt)
    .filter(Boolean)
    .sort()[0] ?? null);
</script>
