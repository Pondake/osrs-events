<template>
    <u-modal v-model:open="isOpen" :title="$t('teams.manage_members', { name: team.name })">
        <template #body>
            <div class="flex flex-col gap-4">
                <div>
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">{{ $t('teams.members') }}</p>
                    <div v-if="team.members.length" class="flex flex-col gap-1">
                        <div v-for="member in team.members" :key="member.id" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-elevated">
                            <u-avatar :src="member.user.avatar_url ?? undefined" size="xs" />
                            <span class="text-sm flex-1">{{ member.user.nickname ?? member.user.discord_username }}</span>
                            <u-button variant="ghost" color="error" size="xs" icon="i-lucide-x" @click="removeMember(member.user)" />
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
                        <p v-if="search && !results.length" class="text-xs text-muted italic px-1 py-2">{{ $t('teams.no_users_found') }}</p>
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

const props = defineProps({
    open: { type: Boolean, default: false },
    team: { type: Object, required: true },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const search = ref('');
const results = ref([]);

let searchTimeout;
function debouncedSearch(value) {
    clearTimeout(searchTimeout);
    if (!value) {
        results.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        const response = await fetch(`/teams/${props.team.id}/users/search?search=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        });
        results.value = await response.json();
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

function removeMember(user) {
    router.delete(`/teams/${props.team.id}/members/${user.id}`, { preserveScroll: true });
}
</script>
