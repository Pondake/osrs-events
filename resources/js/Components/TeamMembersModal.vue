<template>
    <u-modal v-model:open="isOpen" :title="$t('teams.manage_members', { name: team.name })">
        <template #body>
            <div class="flex flex-col gap-4">
                <div>
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">{{ $t('teams.members') }}</p>
                    <div v-if="team.members.length" class="flex flex-col gap-1">
                        <div v-for="member in team.members" :key="member.id" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-elevated">
                            <u-avatar :src="member.user?.avatar_url ?? undefined" size="xs" />
                            <span class="text-sm flex-1 min-w-0 truncate">{{ displayName(member) }}</span>

                            <u-badge
                                v-if="member.role !== 'MEMBER'"
                                :label="$t(`teams.role_${member.role.toLowerCase()}`)"
                                :color="member.role === 'OWNER' ? 'warning' : 'info'"
                                variant="subtle"
                                size="sm"
                            />

                            <!-- Promotion is the owner's alone (see
                                 TeamController::updateMemberRole), so a
                                 manager sees the roles without the switch. -->
                            <u-button
                                v-if="canPromote && member.role !== 'OWNER'"
                                variant="ghost"
                                color="neutral"
                                size="xs"
                                :icon="member.role === 'MANAGER' ? 'i-lucide-shield-minus' : 'i-lucide-shield-plus'"
                                :aria-label="member.role === 'MANAGER' ? $t('teams.demote') : $t('teams.promote')"
                                :title="member.role === 'MANAGER' ? $t('teams.demote') : $t('teams.promote')"
                                @click="setRole(member, member.role === 'MANAGER' ? 'MEMBER' : 'MANAGER')"
                            />

                            <!-- Removing the owner would leave a team nobody
                                 can delete; the server refuses it too. -->
                            <u-button
                                v-if="member.role !== 'OWNER'"
                                variant="ghost"
                                color="error"
                                size="xs"
                                icon="i-lucide-x"
                                :aria-label="$t('teams.remove_member')"
                                @click="removeMember(member.user)"
                            />
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted italic px-1">{{ $t('teams.no_members') }}</p>
                </div>

                <u-separator />

                <div>
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">{{ $t('teams.add_member') }}</p>
                    <u-input v-model="search" :placeholder="$t('teams.search_users_placeholder')" icon="i-lucide-search" class="w-full mb-2" @update:model-value="debouncedSearch" />
                    <div class="flex flex-col gap-1 max-h-52 overflow-y-auto">
                        <div
                            v-for="user in results"
                            :key="user.id"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-elevated cursor-pointer transition-colors"
                            @click="addMember(user)"
                        >
                            <u-avatar :src="user.avatar_url ?? undefined" size="xs" />
                            <span class="text-sm flex-1">{{ user.nickname ?? user.discord_username }}</span>
                        </div>
                        <p v-if="search && !results.length && !searching" class="text-xs text-muted italic px-1 py-2">{{ $t('teams.no_users_found') }}</p>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <u-button color="neutral" variant="outline" :label="$t('common.close')" class="ml-auto" @click="isOpen = false" />
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    open: { type: Boolean, default: false },
    team: { type: Object, required: true },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

// Handing out management is the owner's call; the server enforces the same
// split, this only keeps the button from being there to click.
const canPromote = computed(() => props.team.viewerRole === 'OWNER' || props.team.canDelete);

// A user row can be missing entirely — team_members cascades on user delete,
// but a member whose account is mid-deletion still renders here for a beat.
function displayName(member) {
    return member.user?.nickname ?? member.user?.discord_username ?? trans('common.unknown');
}

const search = ref('');
const results = ref([]);
const searching = ref(false);

let searchTimeout;
function debouncedSearch(value) {
    clearTimeout(searchTimeout);
    if (!value) {
        results.value = [];
        searching.value = false;
        return;
    }
    searching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/teams/${props.team.id}/users/search?search=${encodeURIComponent(value)}`, {
                headers: { Accept: 'application/json' },
            });
            // A 403 (someone lost manage rights while the modal was open) or
            // a throttle answers with a body that is not a list, and this
            // took it anyway — v-for over an object renders nothing and the
            // empty state never appears, which reads as "no users found".
            const data = response.ok ? await response.json() : [];
            results.value = Array.isArray(data) ? data : [];
        } catch (error) {
            console.error(error);
            results.value = [];
        } finally {
            searching.value = false;
        }
    }, 250);
}

function addMember(user) {
    router.post(`/teams/${props.team.id}/members`, { user_id: user.id }, {
        preserveScroll: true,
        onSuccess: () => {
            search.value = '';
            results.value = [];
        },
    });
}

function setRole(member, role) {
    router.patch(`/teams/${props.team.id}/members/${member.user.id}`, { role }, { preserveScroll: true });
}

function removeMember(user) {
    router.delete(`/teams/${props.team.id}/members/${user.id}`, { preserveScroll: true });
}
</script>
