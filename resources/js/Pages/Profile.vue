<template>
    <Head :title="$t('profile.title')" />

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

                            <p v-if="user.nickname && user.discordUsername" class="text-xs text-muted mt-0.5">{{ $t('profile.username') }}: {{ user.discordUsername }}</p>

                            <div class="flex flex-wrap gap-2 mt-3">
                                <u-badge v-for="role in roles" :key="role" :color="roleColor(role)" variant="subtle" :icon="roleIcon(role)" :label="role" />
                            </div>
                        </div>
                    </div>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('profile.account_settings') }}</span>
                    </template>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <p class="font-medium">{{ $t('profile.discord_account') }}</p>
                                <p class="text-sm text-muted">
                                    {{ hasDiscord ? $t('profile.discord_connected_as', { name: user.discordUsername }) : $t('profile.no_discord_desc') }}
                                </p>
                            </div>
                            <u-button
                                v-if="hasDiscord"
                                :disabled="!hasPassword"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                :label="$t('profile.disconnect_discord')"
                                :title="!hasPassword ? $t('profile.discord_disconnect_needs_password') : undefined"
                                @click="disconnectDiscord"
                            />
                            <u-button v-else :href="route('profile.discord.connect')" color="primary" variant="outline" size="sm" icon="i-simple-icons-discord" :label="$t('profile.connect_discord')" />
                        </div>

                        <u-separator />

                        <div>
                            <p class="font-medium mb-1">{{ hasPassword ? $t('profile.change_password') : $t('profile.set_password') }}</p>
                            <p v-if="!hasPassword" class="text-sm text-muted mb-3">{{ $t('profile.no_password_desc') }}</p>

                            <form class="space-y-3 max-w-sm" @submit.prevent="submitPassword">
                                <u-form-field v-if="hasPassword" :label="$t('profile.current_password')" :error="passwordForm.errors.current_password" required>
                                    <u-input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="w-full" />
                                </u-form-field>
                                <u-form-field :label="$t('profile.new_password')" :description="$t('auth.password_requirements')" :error="passwordForm.errors.password" required>
                                    <u-input v-model="passwordForm.password" type="password" autocomplete="new-password" class="w-full" />
                                </u-form-field>
                                <u-form-field :label="$t('profile.confirm_new_password')" required>
                                    <u-input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
                                </u-form-field>
                                <u-button
                                    type="submit"
                                    color="primary"
                                    size="sm"
                                    :loading="passwordForm.processing"
                                    :label="hasPassword ? $t('profile.change_password') : $t('profile.set_password')"
                                />
                            </form>
                        </div>
                    </div>
                </u-card>

                <div>
                    <h3 class="text-lg font-semibold text-highlighted mb-4">{{ $t('profile.your_boards') }}</h3>

                    <div v-if="!playerBoards.length" class="text-center py-8 text-muted">
                        <u-icon name="i-lucide-layout-grid" class="size-10 mx-auto mb-3 block" />
                        <p>{{ $t('profile.no_boards') }}</p>
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
                                        <span><u-icon name="i-lucide-map-pin" class="inline mr-1" />{{ $t('board.tile', { n: pb.current_position + 1 }) }}</span>
                                        <span><u-icon name="i-lucide-circle-check" class="inline mr-1" />{{ pb.completed_tiles.length }} {{ $t('profile.tiles_completed') }}</span>
                                    </div>
                                </div>

                                <div class="w-32 shrink-0">
                                    <div class="flex justify-between text-xs text-muted mb-1">
                                        <span>{{ $t('profile.progress') }}</span>
                                        <span>{{ progressPct(pb) }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all" :style="{ width: `${progressPct(pb)}%` }" />
                                    </div>
                                </div>

                                <u-button :href="`/boards/${pb.board.id}`" icon="i-lucide-play" color="primary" variant="outline" size="sm" :label="$t('boards.play')" />
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
import { Head, router, useForm } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import { formatBoardSize, BOARD_TILE_COUNT } from '@/Support/board';

defineProps({
    roles: { type: Array, required: true },
    playerBoards: { type: Array, required: true },
    hasPassword: { type: Boolean, required: true },
    hasDiscord: { type: Boolean, required: true },
});

const { user } = useAuth();

const editing = ref(false);
const nicknameInput = ref('');
const form = useForm({ nickname: '' });

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function submitPassword() {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function disconnectDiscord() {
    router.delete('/profile/discord', { preserveScroll: true });
}

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
