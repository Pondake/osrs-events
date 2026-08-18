<template>
    <Head title="Teams" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <h1 class="text-3xl font-bold text-highlighted">Teams</h1>
                    <u-button color="primary" icon="i-lucide-plus" label="Create team" @click="showCreateModal = true" />
                </div>

                <div v-if="!teams.length" class="text-center py-16">
                    <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />
                    <p class="text-lg font-medium">No teams yet</p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <u-card v-for="team in teams" :key="team.id">
                        <template #header>
                            <div class="flex items-center gap-3">
                                <u-avatar :src="team.icon_url ?? undefined" :alt="team.name" size="sm" />
                                <div>
                                    <div class="font-semibold">{{ team.name }}</div>
                                    <div v-if="team.guild_name" class="text-xs text-muted">{{ team.guild_name }}</div>
                                </div>
                            </div>
                        </template>

                        <ul class="space-y-1.5">
                            <li v-for="member in team.members" :key="member.id" class="flex items-center justify-between gap-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <u-avatar :src="member.user.avatar_url ?? undefined" size="3xs" />
                                    <span>{{ member.user.nickname ?? member.user.discord_username }}</span>
                                </div>
                                <u-button
                                    v-if="canManage"
                                    icon="i-lucide-x"
                                    size="xs"
                                    color="neutral"
                                    variant="ghost"
                                    @click="removeMember(team, member.user)"
                                />
                            </li>
                        </ul>

                        <template v-if="canManage" #footer>
                            <u-button size="xs" color="neutral" variant="outline" label="Delete team" icon="i-lucide-trash-2" @click="destroyTeam(team)" />
                        </template>
                    </u-card>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <team-settings-modal v-model:open="showCreateModal" />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import ClientOnly from '@/Components/ClientOnly.vue';

const TeamSettingsModal = defineAsyncComponent(() => import('@/Components/TeamSettingsModal.vue'));

defineProps({
    teams: { type: Array, required: true },
});

const { isAdmin } = useAuth();
// Real per-team TEAM_MANAGER membership check (old assertManagerOrAdmin())
// isn't available client-side without shipping every user's role set down —
// this UI-level gate only controls whether the buttons show; the server
// side (TeamController::authorizeManage) is the actual enforcement and
// re-checks properly, so hiding the button for a manager who isn't admin is
// the only cost of this simplification, not a security gap.
const canManage = computed(() => isAdmin.value);

const showCreateModal = ref(false);

function removeMember(team, user) {
    router.delete(`/teams/${team.id}/members/${user.id}`, { preserveScroll: true });
}

function destroyTeam(team) {
    router.delete(`/teams/${team.id}`, { preserveScroll: true });
}
</script>
