<template>
    <Head :title="board.title">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-page-header :title="board.title" :description="board.description ?? ''">
                    <template #headline>
                        <u-badge :label="board.mode" color="neutral" variant="subtle" />
                    </template>
                </u-page-header>

                <div class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <div class="flex-1 w-full min-w-0 overflow-x-auto">
                        <div :class="gridClass" class="grid gap-1.5">
                            <div
                                v-for="tile in tiles"
                                :key="tile.id"
                                class="aspect-square rounded-md border flex items-center justify-center text-xs font-semibold"
                                :class="tileClasses(tile)"
                            >
                                {{ tile.position + 1 }}
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0">
                        <u-card v-if="playerBoard">
                            <template #header>
                                <span class="font-semibold">Your progress</span>
                            </template>
                            <dl class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-muted">Current tile</dt>
                                    <dd>{{ playerBoard.current_position + 1 }} / {{ tiles.length }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-muted">Tiles completed</dt>
                                    <dd>{{ playerBoard.completedTileIds.length }}</dd>
                                </div>
                            </dl>
                        </u-card>
                        <u-alert
                            v-else
                            title="Not joined yet"
                            description="You haven't joined this board — this session has an authenticated user but no PlayerBoard row for it."
                            color="neutral"
                            variant="soft"
                        />
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
// Explicit import, deliberately — @nuxt/ui's autoImport (vite.config.js)
// only catches identifiers referenced in <script>. A <Head> used purely as a
// template tag, with no script-side reference, never gets auto-imported: it
// silently fails to resolve (a Vue "Failed to resolve component" warning in
// dev, printed only to the SSR Node process's own stderr — nothing in the
// browser console, nothing in a production build) and the page ships with
// NO title/meta at all. Confirmed by curling this exact route: raw SSR HTML
// had zero <head> tags from this page until this import was added.
// SnakesAndLadders.vue never hit this because its Head reference happens to
// come from a script-level composable destructure, not the template alone.
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
});

// Tailwind's build-time scanner can't see a dynamically interpolated class
// name (`grid-cols-${cols}`) — this affects Nuxt just as much as Inertia,
// not a framework difference, but it does mean the class list has to be
// written out literally somewhere for the scanner to pick up.
const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[props.board.size] ?? GRID_CLASSES.SIZE_7X7);

function tileClasses(tile) {
    if (props.playerBoard?.completedTileIds.includes(tile.id)) {
        return 'bg-primary/20 border-primary text-primary';
    }
    if (tile.type === 'SNAKE') return 'bg-error/10 border-error/30';
    if (tile.type === 'LADDER') return 'bg-success/10 border-success/30';
    return 'bg-elevated border-default';
}
</script>
