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
                    <template #links>
                        <u-button
                            v-if="canEdit"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-settings"
                            label="Edit board"
                            @click="showSettingsModal = true"
                        />
                    </template>
                </u-page-header>

                <div class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <div class="flex-1 w-full min-w-0 overflow-x-auto">
                        <div :class="gridClass" class="grid gap-1.5">
                            <!-- Not gated on playerBoard existing — reaching this
                                 page at all already implies BoardAccess (see
                                 BoardController::show()'s access-gate redirect),
                                 and PlayerBoardController lazily creates the
                                 PlayerBoard row on first roll/toggle, same as the
                                 old getOrCreatePlayerBoard(). Gating the click on
                                 playerBoard already existing was a genuine
                                 dead-end bug: a brand-new player could never
                                 start, since nothing before this point ever
                                 creates that row. Caught by testing the actual
                                 cold-start flow through a real browser, not just
                                 curling a pre-seeded player's board. -->
                            <button
                                v-for="tile in tiles"
                                :key="tile.id"
                                type="button"
                                class="aspect-square rounded-md border flex items-center justify-center text-xs font-semibold transition-colors cursor-pointer hover:border-primary"
                                :class="tileClasses(tile)"
                                :title="tile.title_override ?? tile.task?.title"
                                @click="toggleTile(tile)"
                            >
                                {{ tile.position + 1 }}
                            </button>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">
                        <u-card>
                            <template #header>
                                <span class="font-semibold">Your progress</span>
                            </template>
                            <dl v-if="playerBoard" class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-muted">Current tile</dt>
                                    <dd>{{ playerBoard.current_position + 1 }} / {{ tiles.length }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-muted">Tiles completed</dt>
                                    <dd>{{ playerBoard.completedTileIds.length }}</dd>
                                </div>
                                <div v-if="board.dice_roll_limit" class="flex justify-between">
                                    <dt class="text-muted">Rolls today</dt>
                                    <dd>{{ playerBoard.dice_rolls_today }} / {{ board.dice_roll_limit }}</dd>
                                </div>
                            </dl>
                            <p v-else class="text-sm text-muted">
                                Roll the dice or complete a tile to get started — your first roll starts your progress.
                            </p>
                            <template #footer>
                                <u-button
                                    color="primary"
                                    block
                                    icon="i-lucide-dice-6"
                                    label="Roll dice"
                                    :loading="rolling"
                                    @click="roll"
                                />
                            </template>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal v-model:open="showSettingsModal" :board="board" />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { defineAsyncComponent } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const props = defineProps({
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
});

const showSettingsModal = ref(false);
const rolling = ref(false);

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

function roll() {
    rolling.value = true;
    router.post(`/boards/${props.board.id}/roll`, {}, { preserveScroll: true, onFinish: () => (rolling.value = false) });
}

function toggleTile(tile) {
    router.post(`/boards/${props.board.id}/tiles/${tile.id}/toggle`, {}, { preserveScroll: true });
}
</script>
