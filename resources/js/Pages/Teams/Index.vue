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

                <!-- Grouped by WHY each team is on the page. The list used
                     to be one flat grid, which for an admin meant every team
                     on the site with nothing saying which were theirs, which
                     came from a shared Discord server, and which they could
                     only see because they are an admin.
                     Useful to everyone, not only admins — "yours" and "your
                     server's" are different kinds of team, and most people
                     only ever see the first two groups. -->
                <div v-else class="space-y-10">
                    <section v-for="group in groups" :key="group.reason">
                        <div class="flex items-baseline gap-3 mb-4">
                            <h2 class="text-lg font-semibold text-highlighted">{{ group.title }}</h2>
                            <span class="text-sm text-muted">{{ group.teams.length }}</span>
                        </div>
                        <p class="text-sm text-muted -mt-3 mb-4">{{ group.description }}</p>

                        <!-- CSS multi-column, not `grid` — a grid gives every
                             card in a row the same height, so a team with a
                             two-line member list and one with fifteen sat in
                             equally tall boxes with a lot of empty card below
                             the short one. Columns let each card be exactly
                             as tall as its own content and stack the next one
                             straight underneath it. `break-inside-avoid` on
                             each card stops a browser splitting one card's
                             content across two columns. -->
                        <div class="columns-1 sm:columns-2 lg:columns-3 gap-6">
                            <u-card v-for="team in group.teams" :key="team.id" class="break-inside-avoid mb-6">
                        <template #header>
                            <div class="flex items-center gap-3">
                                <team-avatar :name="team.name" :icon-url="team.icon_url" :guild-icon-url="team.guild_icon_url" size="sm" />
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
                                <!-- `alt` is what UAvatar derives its initials from — without it
                                     a member with no Discord picture rendered an empty
                                     circle. Same fallback chain as the name beside it,
                                     see TeamAvatar. -->
                                <u-avatar :src="member.user?.avatar_url ?? undefined" :alt="memberName(member)" size="3xs" />
                                <span class="truncate">{{ memberName(member) }}</span>
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
                                <confirm-popover
                                    v-if="team.canDelete"
                                    :message="$t('teams.delete_confirm', { name: team.name })"
                                    :confirm-label="$t('common.delete')"
                                    :loading="deletingTeamId === team.id"
                                    @confirm="(note, done) => destroyTeam(team, done)"
                                >
                                    <u-button size="xs" color="error" variant="outline" :label="$t('common.delete')" icon="i-lucide-trash-2" />
                                </confirm-popover>
                            </div>
                        </template>
                            </u-card>
                        </div>
                    </section>
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
import ConfirmPopover from '@/Components/ConfirmPopover.vue';
import TeamAvatar from '@/Components/TeamAvatar.vue';

const TeamSettingsModal = defineAsyncComponent(() => import('@/Components/TeamSettingsModal.vue'));
const TeamMembersModal = defineAsyncComponent(() => import('@/Components/TeamMembersModal.vue'));

const props = defineProps({
    // Each team carries viewerRole / canManage / canDelete, decided server
    // side by Team::isManagedBy()/isOwnedBy(). The page used to gate its
    // buttons on isAdmin alone, which meant creating a team gave you a card
    // you could do nothing with.
    teams: { type: Array, required: true },
});

// Name and initials come from one place: UAvatar derives the initials from
// `alt`, so the avatar has to be handed the same string the label prints —
// without it, a member with no Discord picture was an empty grey circle.
const memberName = (member) =>
    member.user?.nickname ?? member.user?.discord_username ?? trans('common.unknown');

/**
 * The three ways a team can end up on this page, in order of how strong the
 * claim is.
 *
 * `reason` is decided server-side (TeamController::visibilityReason) because
 * the client has no business knowing which Discord servers somebody is in.
 * Empty groups are dropped: most people only ever have the first two, and a
 * heading over nothing reads as a bug.
 */
const GROUP_ORDER = ['member', 'guild', 'admin'];

const groups = computed(() => GROUP_ORDER
    .map((reason) => ({
        reason,
        title: trans(`teams.group_${reason}`),
        description: trans(`teams.group_${reason}_desc`),
        teams: props.teams.filter((team) => team.reason === reason),
    }))
    .filter((group) => group.teams.length > 0));

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

const deletingTeamId = ref(null);

function destroyTeam(team, done) {
    // Deleting a team takes every member row and every board assignment with
    // it, and there is no undo — the one action here worth a confirm. A
    // popover anchored to the button, not window.confirm(): a native browser
    // dialog can't be styled and reads as a jarring interruption next to
    // every other confirm in the app.
    deletingTeamId.value = team.id;

    router.delete(`/teams/${team.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingTeamId.value = null;
            done?.();
        },
    });
}
</script>
