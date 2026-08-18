<template>
    <Head :title="`${board.title} — Leaderboard`" />

    <u-main>
        <u-page>
            <u-container class="py-12 max-w-2xl">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ board.title }}</h1>
                        <p class="text-muted">Leaderboard</p>
                    </div>
                    <u-button :href="`/boards/${board.id}`" color="neutral" variant="outline" icon="i-lucide-arrow-left" label="Back to board" />
                </div>

                <div v-if="!entries.length" class="text-center py-16 text-muted">No players have joined this board yet.</div>

                <div v-else class="divide-y divide-default rounded-lg ring ring-default bg-default">
                    <div v-for="entry in entries" :key="entry.playerId" class="flex items-center gap-4 px-4 py-3">
                        <div class="w-8 text-center font-bold" :class="entry.rank <= 3 ? 'text-primary' : 'text-muted'">
                            {{ entry.rank }}
                        </div>

                        <u-avatar :src="(entry.team?.icon_url ?? entry.user?.avatar_url) ?? undefined" size="sm" />

                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">
                                {{ entry.team?.name ?? entry.user?.nickname ?? entry.user?.discord_username }}
                            </div>
                            <div class="text-xs text-muted">
                                Tile {{ entry.currentPosition + 1 }} / {{ totalTiles }} — {{ entry.tilesRemaining }} to go
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <u-icon v-if="entry.pathHasLadder" name="i-lucide-arrow-up-from-line" class="text-success" title="A ladder lies ahead" />
                            <u-icon v-if="entry.pathHasSnake" name="i-lucide-arrow-down-to-line" class="text-error" title="A snake lies ahead" />
                        </div>
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    board: { type: Object, required: true },
    totalTiles: { type: Number, required: true },
    entries: { type: Array, required: true },
});
</script>
