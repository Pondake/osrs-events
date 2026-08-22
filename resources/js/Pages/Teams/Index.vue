<template>
    <Head :title="$t('teams.title')" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <!-- Stacks on a phone. Side by side, the title block was left
                     with a ~150px column and the description broke into
                     four ragged lines beside a button that needed none of
                     the room it was taking. -->
                <div class="flex flex-col items-start gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ $t('teams.title') }}</h1>
                        <p class="text-sm text-muted mt-1">{{ $t('teams.subtitle') }}</p>
                    </div>
                    <u-button color="primary" icon="i-lucide-plus" :label="$t('teams.create_team')" @click="openCreate" />
                </div>

                <div v-if="!teams.length" class="text-center py-16">
                    <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />
                    <p class="text-lg font-medium">{{ $t('teams.no_teams') }}</p>
                    <p class="text-sm text-muted mt-1 mb-6">{{ $t('teams.no_teams_desc') }}</p>
                    <u-button color="primary" icon="i-lucide-plus" :label="$t('teams.create_team')" @click="openCreate" />
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <u-card v-for="team in teams" :key="team.id">
                        <template #header>
                            <div class="flex items-center gap-3">
                                <u-avatar :src="team.icon_url ?? undefined" :alt="team.name" size="sm" />
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ team.name }}</div>
                                    <div v-if="team.guild_name" class="text-xs text-muted truncate">{{ team.guild_name }}</div>
                                </div>
                                <!-- Says why the buttons below are there (or
                                     are not) without making anyone guess. -->
                                <u-badge
                                    v-if="team.viewerRole && team.viewerRole !== 'MEMBER'"
                                    :label="$t(`teams.role_${team.viewerRole.toLowerCase()}`)"
                                    :color="team.viewerRole === 'OWNER' ? 'warning' : 'info'"
                                    variant="subtle"
                                    size="sm"
                                    class="ml-auto shrink-0"
                                />
                            </div>
                        </template>

                        <ul class="space-y-1.5">
                            <li v-for="member in team.members" :key="member.id" class="flex items-center gap-2 text-sm">
                                <u-avatar :src="member.user?.avatar_url ?? undefined" size="3xs" />
                                <span class="truncate">{{ member.user?.nickname ?? member.user?.discord_username ?? $t('common.unknown') }}</span>
                                <u-icon
                                    v-if="member.role === 'OWNER'"
                                    name="i-lucide-crown"
                                    class="size-3.5 text-warning shrink-0"
                                    :title="$t('teams.role_owner')"
                                />
                                <u-icon
                                    v-else-if="member.role === 'MANAGER'"
                                    name="i-lucide-shield"
                                    class="size-3.5 text-info shrink-0"
                                    :title="$t('teams.role_manager')"
                                />
                            </li>
                            <li v-if="!team.members.length" class="text-sm text-muted italic">{{ $t('teams.no_members') }}</li>
                        </ul>

                        <!-- Per-team flags from the server, not one global
                             isAdmin check: the person who created a team can
                             manage it, a promoted manager can too, and only
                             the owner can delete it. TeamController re-checks
                             every one of these on the write itself. -->
                        <template v-if="team.canManage" #footer>
                            <div class="flex flex-wrap gap-2">
                                <u-button size="xs" color="neutral" variant="outline" :label="$t('teams.manage_members_short')" icon="i-lucide-users" @click="managingTeamId = team.id" />
                                <u-button size="xs" color="neutral" variant="outline" :label="$t('common.edit')" icon="i-lucide-pencil" @click="openEdit(team)" />
                                <u-button v-if="team.canDelete" size="xs" color="error" variant="outline" :label="$t('common.delete')" icon="i-lucide-trash-2" @click="destroyTeam(team)" />
                            </div>
                        </template>
                    </u-card>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <team-settings-modal v-model:open="showSettingsModal" :team="editingTeam" />
            <team-members-modal
                v-if="managingTeam"
                :open="managingTeamId !== null"
                :team="managingTeam"
                @update:open="(v) => !v && (managingTeamId = null)"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';

const TeamSettingsModal = defineAsyncComponent(() => import('@/Components/TeamSettingsModal.vue'));
const TeamMembersModal = defineAsyncComponent(() => import('@/Components/TeamMembersModal.vue'));

const props = defineProps({
    // Each team carries viewerRole / canManage / canDelete, decided server
    // side by Team::isManagedBy()/isOwnedBy(). The page used to gate its
    // buttons on isAdmin alone, which meant creating a team gave you a card
    // you could do nothing with.
    teams: { type: Array, required: true },
});

const showSettingsModal = ref(false);
const editingTeam = ref(null);

function openCreate() {
    editingTeam.value = null;
    showSettingsModal.value = true;
}

function openEdit(team) {
    editingTeam.value = team;
    showSettingsModal.value = true;
}

// Tracked by ID, not by holding the team object itself — router.post()
// from inside TeamMembersModal (adding/removing a member) triggers an
// Inertia reload that replaces `teams` with entirely new objects. A stored
// object reference goes stale at that point (confirmed live: the modal kept
// showing "No members yet" after a member was successfully added and the
// page's own list updated correctly) since Vue's reactivity can't follow a
// plain JS variable holding an old object across props being swapped out
// wholesale. Deriving the current team via a computed lookup by ID instead
// always reads the live prop.
const managingTeamId = ref(null);
const managingTeam = computed(() => props.teams.find((t) => t.id === managingTeamId.value) ?? null);

function destroyTeam(team) {
    // Deleting a team takes every member row and every board assignment with
    // it, and there is no undo — the one action here worth a confirm.
    if (!window.confirm(trans('teams.delete_confirm', { name: team.name }))) return;

    router.delete(`/teams/${team.id}`, { preserveScroll: true });
}
</script>
