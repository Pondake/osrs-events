<template>
    <Head title="Profile" />

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12 space-y-8">
                <u-card>
                    <div class="flex items-center gap-6">
                        <u-avatar :src="user.avatarUrl ?? undefined" :alt="user.discordUsername" size="3xl" />

                        <div class="flex-1 min-w-0">
                            <div v-if="!editing" class="flex items-center gap-2">
                                <h2 class="text-2xl font-bold text-highlighted">{{ user.nickname ?? user.discordUsername }}</h2>
                                <u-button size="xs" color="neutral" variant="ghost" icon="i-lucide-pencil" @click="startEditing" />
                            </div>
                            <div v-else class="flex items-center gap-2 flex-wrap">
                                <u-input v-model="nicknameInput" :placeholder="user.discordUsername" size="sm" class="flex-1" />
                                <u-button size="sm" color="primary" icon="i-lucide-check" :loading="form.processing" @click="save" />
                                <u-button size="sm" color="neutral" variant="ghost" icon="i-lucide-x" @click="editing = false" />
                            </div>

                            <p v-if="user.nickname" class="text-xs text-muted mt-0.5">Discord: {{ user.discordUsername }}</p>

                            <div class="flex flex-wrap gap-2 mt-3">
                                <u-badge v-for="role in roles" :key="role" :color="roleColor(role)" variant="subtle" :icon="roleIcon(role)" :label="role" />
                            </div>
                        </div>
                    </div>
                </u-card>

                <div>
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Your boards</h3>

                    <div v-if="!playerBoards.length" class="text-center py-8 text-muted">
                        <u-icon name="i-lucide-layout-grid" class="size-10 mx-auto mb-3 block" />
                        <p>You haven't joined any boards yet.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <u-card v-for="pb in playerBoards" :key="pb.id">
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-1 flex-wrap">
                                        <a :href="`/boards/${pb.board.id}`" class="text-lg font-semibold hover:text-primary transition-colors truncate">
                                            {{ pb.board.title }}
                                        </a>
                                        <u-badge color="primary" variant="subtle" :label="formatBoardSize(pb.board.size)" />
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-muted flex-wrap">
                                        <span><u-icon name="i-lucide-map-pin" class="inline mr-1" />Tile {{ pb.current_position + 1 }}</span>
                                        <span><u-icon name="i-lucide-circle-check" class="inline mr-1" />{{ pb.completed_tiles.length }} completed</span>
                                    </div>
                                </div>

                                <div class="w-32 shrink-0">
                                    <div class="flex justify-between text-xs text-muted mb-1">
                                        <span>Progress</span>
                                        <span>{{ progressPct(pb) }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all" :style="{ width: `${progressPct(pb)}%` }" />
                                    </div>
                                </div>

                                <u-button :href="`/boards/${pb.board.id}`" icon="i-lucide-play" color="primary" variant="outline" size="sm" label="Play" />
                            </div>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import { formatBoardSize, BOARD_TILE_COUNT } from '@/Support/board';

defineProps({
    roles: { type: Array, required: true },
    playerBoards: { type: Array, required: true },
});

const { user } = useAuth();

const editing = ref(false);
const nicknameInput = ref('');
const form = useForm({ nickname: '' });

function startEditing() {
    nicknameInput.value = user.value.nickname ?? '';
    editing.value = true;
}

function save() {
    form.nickname = nicknameInput.value.trim() || null;
    form.patch('/profile', { onSuccess: () => (editing.value = false), preserveScroll: true });
}

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', TEAM_MANAGER: 'info', PLAYER: 'primary' };
const ROLE_ICONS = { ADMIN: 'i-lucide-shield-check', EDITOR: 'i-lucide-pencil', TEAM_MANAGER: 'i-lucide-users', PLAYER: 'i-lucide-user' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';
const roleIcon = (name) => ROLE_ICONS[name] ?? 'i-lucide-user';

function progressPct(pb) {
    const total = BOARD_TILE_COUNT[pb.board.size] ?? 25;
    if (total <= 1) return 0;
    const pos = Math.max(0, pb.current_position);
    return Math.min(99, Math.floor((pos / (total - 1)) * 100));
}
</script>
