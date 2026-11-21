<template>
    <div v-if="players.length">
        <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">
            {{ $t('board.detail_standing_here', { count: players.length }) }}
        </p>

        <p v-if="!named" class="text-sm text-muted">
            {{ $tChoice('board.detail_standing_count', players.length, { count: players.length }) }}
        </p>

        <ul v-else class="space-y-1.5">
            <li v-for="p in shown" :key="p.id" class="flex items-center gap-2 text-sm">
                <u-avatar :src="p.avatarUrl ?? undefined" :alt="p.name" size="2xs" class="shrink-0" />
                <span class="truncate">{{ p.name }}</span>
                <span v-if="p.isYou" class="text-xs text-primary font-medium shrink-0">{{ $t('board.detail_you') }}</span>
            </li>

            <li v-if="hidden" class="text-sm text-muted">
                <button type="button" class="cursor-pointer underline underline-offset-2 hover:text-default focus-visible:outline-2 focus-visible:outline-primary rounded-sm" @click="expanded = true">
                    {{ $t('board.detail_more', { count: hidden }) }}
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    // [{ id, name, avatarUrl, isYou }]
    players: { type: Array, default: () => [] },
    named: { type: Boolean, default: true },
});

const SHOWN = 8;

const expanded = ref(false);

const shown = computed(() => (expanded.value ? props.players : props.players.slice(0, SHOWN)));
const hidden = computed(() => props.players.length - shown.value.length);
</script>
