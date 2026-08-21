<template>
    <div class="space-y-2">
        <!-- What is currently attached, if anything. Shown instead of the
             search box rather than beside it: a filled slot with a live
             search under it invites a second pick that silently replaces the
             first. -->
        <div v-if="modelValue" class="flex items-center gap-3 rounded-md ring ring-default px-3 py-2">
            <img v-if="modelValue.icon_url" :src="modelValue.icon_url" alt="" class="size-6 object-contain shrink-0" />
            <u-icon v-else name="i-lucide-square-dashed" class="size-5 text-muted shrink-0" />

            <span class="flex-1 min-w-0 truncate text-sm">{{ modelValue.title }}</span>

            <a
                v-if="modelValue.wiki_url"
                :href="modelValue.wiki_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-muted hover:text-primary transition-colors shrink-0"
                :title="$t('tile_editor.open_wiki_page')"
            >
                <u-icon name="i-lucide-external-link" class="size-4" />
            </a>

            <u-button
                icon="i-lucide-x"
                color="neutral"
                variant="ghost"
                size="xs"
                :aria-label="$t('common.clear')"
                @click="emit('update:modelValue', null)"
            />
        </div>

        <template v-else>
            <!-- One search box over the OSRS Wiki, and no source picker.
                 It used to offer "Task library" and "OSRS Wiki" side by
                 side, which asked people to choose between fourteen seeded
                 rows and the whole wiki — a choice with an obvious answer,
                 made once per tile. The library still exists; it is the
                 cache behind this box now (see Task::wikiCacheIsStale),
                 not a place to look. -->
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
                    class="w-full flex items-center gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left disabled:opacity-60"
                    :disabled="importing"
                    @click="choose(result)"
                >
                    <img v-if="result.icon_url" :src="result.icon_url" alt="" class="size-6 object-contain shrink-0" />
                    <u-icon v-else name="i-lucide-file-text" class="size-5 text-muted shrink-0" />
                    <span class="text-sm truncate">{{ result.title }}</span>
                </button>
            </div>

            <!-- Three different nothings, and they need different words: not
                 searched yet, searched and found nothing, or the wiki did not
                 answer. The old editors showed the same blank list for all
                 three, which is what made an integration that did not exist
                 look like one that was broken. -->
            <p v-else-if="failed" class="text-xs text-error px-1">{{ $t('tile_editor.wiki_unavailable') }}</p>
            <p v-else-if="searched && !loading" class="text-xs text-muted italic px-1">{{ $t('tile_editor.no_wiki_results') }}</p>
            <p v-else-if="!search" class="text-xs text-muted px-1">{{ $t('tile_editor.wiki_hint') }}</p>
        </template>
    </div>
</template>

<script setup>
import { ref } from 'vue';

/**
 * Picks what a tile or a bingo square asks for, from the OSRS Wiki.
 *
 * Shared by TileEditModal, BingoSquareModal and TileListEditor, which had
 * near-identical copies of a local-only search — while the home page has
 * promised wiki search the whole time. Choosing a result creates or refreshes
 * a Task server-side (see WikiController::importTask), so both editors keep
 * linking to the one concept they already understood, and that table doubles
 * as the wiki cache.
 */
const props = defineProps({
    // The chosen task, or null. A full object rather than an id, because the
    // filled state needs its title and icon and the parent already has them.
    modelValue: { type: Object, default: null },
    // Scopes the wiki endpoints, which are permissioned per event.
    eventId: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const results = ref([]);
const loading = ref(false);
const importing = ref(false);
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

    // 350ms rather than the 250 the old editors used: this is a round trip to
    // somebody else's server, and a keystroke that outruns it costs them a
    // request for a prefix nobody wanted.
    searchTimeout = setTimeout(() => runSearch(value.trim()), 350);
}

async function runSearch(term) {
    try {
        const response = await fetch(`/events/${props.eventId}/wiki/search?search=${encodeURIComponent(term)}`, {
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

async function choose(result) {
    importing.value = true;

    try {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const response = await fetch(`/events/${props.eventId}/wiki/tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {}),
            },
            body: JSON.stringify({ page_id: result.page_id, title: result.title }),
        });

        if (!response.ok) throw new Error(`wiki import failed: ${response.status}`);

        emit('update:modelValue', (await response.json()).task);
        results.value = [];
        search.value = '';
    } catch (error) {
        console.error(error);
        failed.value = true;
    } finally {
        importing.value = false;
    }
}
</script>
