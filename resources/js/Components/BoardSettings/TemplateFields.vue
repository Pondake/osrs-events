<template>
    <div class="space-y-3 py-2">
        <p class="text-sm text-muted">{{ $t('blueprints.step_desc') }}</p>

        <u-input
            :model-value="search"
            icon="i-lucide-search"
            class="w-full"
            :placeholder="$t('blueprints.search_placeholder')"
            @update:model-value="(value) => emit('search', value)"
        />

        <!-- Always first, and always available. A template is an offer, not a
             gate: somebody who knows what they want should not have to read
             a gallery to get past it. -->
        <button
            type="button"
            class="w-full flex items-center gap-3 p-3 rounded-lg ring text-left transition-colors"
            :class="selectedId === null ? 'ring-primary bg-primary/10' : 'ring-default hover:bg-elevated'"
            @click="emit('skip')"
        >
            <u-icon name="i-lucide-file-plus-2" class="size-5 shrink-0" :class="selectedId === null ? 'text-primary' : 'text-muted'" />
            <span class="min-w-0">
                <span class="block font-medium text-sm">{{ $t('blueprints.from_scratch') }}</span>
                <span class="block text-xs text-muted">{{ $t('blueprints.from_scratch_desc') }}</span>
            </span>
        </button>

        <div v-if="loading" class="flex items-center gap-2 text-sm text-muted px-1 py-3">
            <u-icon name="i-lucide-loader-circle" class="size-4 animate-spin" />
            {{ $t('common.loading') }}
        </div>

        <p v-else-if="!blueprints.length" class="text-sm text-muted px-1 py-3">
            {{ search ? $t('blueprints.none_match') : $t('blueprints.none_yet') }}
        </p>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button
                v-for="blueprint in blueprints"
                :key="blueprint.id"
                type="button"
                class="flex flex-col gap-2 p-3 rounded-lg ring text-left transition-colors"
                :class="selectedId === blueprint.id ? 'ring-primary bg-primary/10' : 'ring-default hover:bg-elevated'"
                @click="emit('apply', blueprint)"
            >
                <span class="flex items-start gap-2">
                    <u-icon
                        :name="typeIcon(blueprint.type)"
                        class="size-4 shrink-0 mt-0.5"
                        :class="selectedId === blueprint.id ? 'text-primary' : 'text-muted'"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium text-sm truncate">{{ blueprint.title }}</span>
                        <span v-if="blueprint.description" class="block text-xs text-muted line-clamp-2 mt-0.5">
                            {{ blueprint.description }}
                        </span>
                    </span>
                </span>

                <!-- What you are actually about to get. Without this the card
                     is the old dropdown with more padding. -->
                <span v-if="summaries[blueprint.id]?.length" class="flex flex-wrap gap-1">
                    <u-badge
                        v-for="chip in summaries[blueprint.id]"
                        :key="chip"
                        :label="chip"
                        color="neutral"
                        variant="subtle"
                        size="sm"
                    />
                </span>

                <!-- Whose format this is. The set that ships with the app and
                     one a clan mate saved last month are different kinds of
                     recommendation. -->
                <span class="text-xs text-muted">
                    {{ blueprint.source === 'global'
                        ? $t('blueprints.source_global')
                        : $t('blueprints.source_clan', { name: blueprint.author ?? $t('common.unknown') }) }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

import { eventTypeMeta } from '@/Support/eventTypes';
import { summariseBlueprint } from '@/Support/blueprint';

/**
 * "Start from a template", as the first step of creating an event.
 *
 * A gallery rather than an autocomplete on the title, because a template now
 * carries the whole shape of an event — grid size, win condition, who can
 * join — and a one-line suggestion cannot show any of that. Picking a format
 * you cannot see is picking a name.
 */
const props = defineProps({
    blueprints: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    search: { type: String, default: '' },
    // The blueprint currently applied, or null for "from scratch".
    selectedId: { type: String, default: null },
});

const emit = defineEmits(['search', 'apply', 'skip']);

/** Computed once per list rather than per render of each card. */
const summaries = computed(() => Object.fromEntries(
    props.blueprints.map((blueprint) => [blueprint.id, summariseBlueprint(blueprint)]),
));

/** The shapes icon stands in for a title-only template, which has no type. */
function typeIcon(type) {
    return eventTypeMeta(type)?.icon ?? 'i-lucide-shapes';
}
</script>
