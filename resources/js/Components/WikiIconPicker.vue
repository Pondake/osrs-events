<template>
    <div class="space-y-2">
        <!-- What is currently set, shown instead of the search box rather
             than beside it — same reasoning as TaskPicker.vue: a filled slot
             with a live search under it invites a second pick that silently
             replaces the first. -->
        <div v-if="modelValue" class="flex items-center gap-3 rounded-md ring ring-default px-3 py-2">
            <img :src="modelValue" alt="" class="size-6 object-contain shrink-0" />

            <span class="flex-1 min-w-0 truncate text-sm text-muted">{{ modelValue }}</span>

            <u-button
                icon="i-lucide-x"
                color="neutral"
                variant="ghost"
                size="xs"
                :aria-label="$t('common.clear')"
                @click="emit('update:modelValue', '')"
            />
        </div>

        <template v-else>
            <u-input
                v-model="search"
                icon="i-lucide-search"
                :placeholder="$t('tile_editor.search_wiki_placeholder')"
                :loading="loading"
                class="w-full"
                @update:model-value="onSearch"
            />

            <div v-if="results.length" class="rounded-md ring ring-default divide-y divide-default max-h-56 overflow-y-auto">
                <button
                    v-for="result in results"
                    :key="result.page_id"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left"
                    @click="choose(result)"
                >
                    <img v-if="result.icon_url" :src="result.icon_url" alt="" class="size-6 object-contain shrink-0" />
                    <u-icon v-else name="i-lucide-file-text" class="size-5 text-muted shrink-0" />
                    <span class="text-sm truncate">{{ result.title }}</span>
                </button>
            </div>

            <!-- Same three-way empty state as TaskPicker.vue, and for the same
                 reason: a blank list for "not searched yet", "no results" and
                 "the wiki didn't answer" all look identical otherwise. -->
            <p v-if="failed" class="text-xs text-error px-1">{{ $t('tile_editor.wiki_unavailable') }}</p>
            <p v-else-if="searched && !loading && !results.length" class="text-xs text-muted italic px-1">{{ $t('tile_editor.no_wiki_results') }}</p>
            <p v-else-if="!search" class="text-xs text-muted px-1">{{ $t('tile_editor.wiki_hint') }}</p>
        </template>
    </div>
</template>

<script setup>
import { ref } from 'vue';

/**
 * Picks an icon straight off the OSRS Wiki, with no Task behind it.
 *
 * TaskPicker.vue does something similar but creates/updates a Task row per
 * choice (WikiController::importTask) — right for a tile or bingo square,
 * which really does want a shared, reusable Task underneath it. A team icon
 * and a task's OWN icon field have nothing to attach a Task to (a task being
 * edited here already IS the row; a team was never one), so this only ever
 * emits a plain icon_url. Same search endpoint's data shape, no scoping to
 * an event: see WikiController::searchGlobal.
 */
const props = defineProps({
    // The chosen icon URL, or ''/null. A plain string, not an object — there
    // is nothing else here worth keeping once a result is picked.
    modelValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const results = ref([]);
const loading = ref(false);
const searched = ref(false);
const failed = ref(false);

let searchTimeout;

function onSearch(value) {
    clearTimeout(searchTimeout);
    failed.value = false;

    if (!value || value.trim().length < 2) {
        results.value = [];
        searched.value = false;
        loading.value = false;

        return;
    }

    loading.value = true;

    // Same 350ms as TaskPicker.vue — a round trip to somebody else's server,
    // and a keystroke that outruns it costs them a request for nothing.
    searchTimeout = setTimeout(() => runSearch(value.trim()), 350);
}

async function runSearch(term) {
    try {
        const response = await fetch(`/wiki/search?search=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) throw new Error(`wiki search failed: ${response.status}`);

        results.value = (await response.json()).results ?? [];
    } catch (error) {
        console.error(error);
        results.value = [];
        failed.value = true;
    } finally {
        loading.value = false;
        searched.value = true;
    }
}

function choose(result) {
    emit('update:modelValue', result.icon_url ?? '');
    results.value = [];
    search.value = '';
}
</script>
