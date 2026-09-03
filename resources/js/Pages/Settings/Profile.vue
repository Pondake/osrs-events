<template>
    <Head :title="$t('settings.profile_title')" />

    <settings-layout current="profile">
        <u-card>
            <div class="flex items-center gap-6">
                <u-avatar :src="user.avatarUrl ?? undefined" :alt="user.nickname ?? user.discordUsername" size="3xl" />

                <div class="flex-1 min-w-0">
                    <div v-if="!editing" class="flex items-center gap-2">
                        <h2 class="text-2xl font-bold text-highlighted">{{ user.nickname ?? user.discordUsername }}</h2>
                        <u-button size="xs" color="neutral" variant="ghost" icon="i-lucide-pencil" @click="startEditing" />
                    </div>
                    <div v-else class="flex items-center gap-2 flex-wrap">
                        <u-input v-model="nicknameInput" :placeholder="user.discordUsername ?? ''" size="sm" class="flex-1" />
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
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="font-medium">{{ $t('onboarding.title') }}</p>
                    <p class="text-sm text-muted">{{ $t('onboarding.welcome_body') }}</p>
                </div>
                <u-button color="neutral" variant="outline" size="sm" icon="i-lucide-rotate-ccw" :label="$t('onboarding.restart')" @click="replayOnboarding" />
            </div>
        </u-card>

        <!-- Used to be its own list here — created/joined tabs, no board
             preview. Removed rather than kept in sync with /my-events, which
             already covers the same "your events" question with real board
             previews and hosted/playing filters (see the header's Events →
             My events nav item). A settings page duplicating a destination
             nav already points to is two places that can disagree about the
             same list, not two features. -->
        <u-card>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="font-medium">{{ $t('profile.your_events') }}</p>
                    <p class="text-sm text-muted">{{ $t('profile.your_events_desc') }}</p>
                </div>
                <u-button color="primary" variant="outline" size="sm" icon="i-lucide-arrow-right" :label="$t('nav.my_boards')" href="/my-events" />
            </div>
        </u-card>
    </settings-layout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import SettingsLayout from '@/Components/SettingsLayout.vue';

defineProps({
    roles: { type: Array, required: true },
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
    form.patch('/settings/profile', { onSuccess: () => (editing.value = false), preserveScroll: true });
}

// Clears onboarding_completed_at; AppRoot watches the shared prop and
// re-opens the modal once the response lands.
function replayOnboarding() {
    router.post('/onboarding/reset', {}, { preserveScroll: true });
}

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', PLAYER: 'primary' };
const ROLE_ICONS = { ADMIN: 'i-lucide-shield-check', EDITOR: 'i-lucide-pencil', PLAYER: 'i-lucide-user' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';
const roleIcon = (name) => ROLE_ICONS[name] ?? 'i-lucide-user';
</script>
