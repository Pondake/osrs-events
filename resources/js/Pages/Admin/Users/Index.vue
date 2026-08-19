<template>
    <Head :title="$t('admin.users_title')" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <h1 class="text-3xl font-bold text-highlighted mb-8">{{ $t('nav.admin_users') }}</h1>

                <u-input v-model="search" :placeholder="$t('admin.search_users_placeholder')" icon="i-lucide-search" class="w-full max-w-sm mb-6" @update:model-value="doSearch" />

                <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
                    <div v-for="user in users" :key="user.id" class="flex items-center justify-between gap-4 px-4 py-3 flex-wrap">
                        <div class="flex items-center gap-3 min-w-0">
                            <u-avatar :src="user.avatar_url ?? undefined" size="sm" />
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ user.nickname ?? user.discord_username ?? user.email }}</div>
                                <div class="text-xs text-muted truncate">{{ user.discord_username ? `@${user.discord_username}` : user.email }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap">
                            <u-badge v-for="ur in user.user_roles" :key="ur.id" :label="ur.role.name" color="primary" variant="subtle" class="cursor-pointer" @click="removeRole(user, ur.role)" />
                            <u-badge v-for="p in user.user_permissions" :key="p.id" :label="p.permission_key" color="neutral" variant="subtle" class="cursor-pointer" @click="revokePermission(user, p.permission_key)" />

                            <u-select
                                :model-value="null"
                                :items="roleOptions"
                                :placeholder="$t('admin.role_placeholder')"
                                size="xs"
                                class="w-28"
                                @update:model-value="(role) => assignRole(user, role)"
                            />
                            <u-select
                                :model-value="null"
                                :items="permissionKeys"
                                :placeholder="$t('admin.permission_placeholder')"
                                size="xs"
                                class="w-36"
                                @update:model-value="(key) => grantPermission(user, key)"
                            />

                            <!-- Hidden rather than disabled for the two cases the
                                 server also refuses (self, admins) — a button that
                                 only ever errors isn't worth showing. -->
                            <u-button
                                v-if="canDelete(user)"
                                icon="i-lucide-trash-2"
                                color="error"
                                variant="ghost"
                                size="xs"
                                :aria-label="$t('admin.delete_user')"
                                :title="$t('admin.delete_user')"
                                @click="confirmDelete(user)"
                            />
                        </div>
                    </div>
                    <p v-if="!users.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_users') }}</p>
                </div>
            </u-container>
        </u-page>

        <client-only>
            <u-modal v-model:open="showDeleteModal" :title="$t('admin.delete_user')">
                <template #body>
                    <p class="text-muted">{{ $t('admin.delete_user_confirm', { name: deleteTarget ? displayName(deleteTarget) : '' }) }}</p>
                </template>
                <template #footer>
                    <div class="flex gap-2 justify-end w-full">
                        <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="showDeleteModal = false" />
                        <u-button color="error" :label="$t('admin.delete_user')" @click="destroyUser" />
                    </div>
                </template>
            </u-modal>
        </client-only>
    </u-main>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import ClientOnly from '@/Components/ClientOnly.vue';

const props = defineProps({
    users: { type: Array, required: true },
    search: { type: String, default: '' },
    permissionKeys: { type: Array, required: true },
});

const { user: currentUser } = useAuth();

// Not fetched from the server — ADMIN/EDITOR/TEAM_MANAGER were the roles
// referenced across the old frontend's isAdmin/isEditor checks and
// assertManagerOrAdmin(). Roles are otherwise freeform strings
// (Role::firstOrCreate in AdminUserController::assignRole), so this list is
// just the known set worth offering in this dropdown, not a hard constraint.
const roleOptions = ['ADMIN', 'EDITOR', 'TEAM_MANAGER', 'PLAYER'];

const search = ref(props.search);

function doSearch(value) {
    router.get('/admin/users', { search: value }, { preserveState: true, replace: true });
}

function assignRole(user, role) {
    router.post(`/admin/users/${user.id}/roles`, { role }, { preserveScroll: true });
}

function removeRole(user, role) {
    router.delete(`/admin/users/${user.id}/roles/${role.id}`, { preserveScroll: true });
}

function grantPermission(user, key) {
    router.post(`/admin/users/${user.id}/permissions`, { permission_key: key }, { preserveScroll: true });
}

function revokePermission(user, key) {
    router.delete(`/admin/users/${user.id}/permissions/${key}`, { preserveScroll: true });
}

const displayName = (user) => user.nickname ?? user.discord_username ?? user.email;

// Mirrors AdminUserController::destroy()'s own two refusals.
const canDelete = (user) => user.id !== currentUser.value?.id && !user.user_roles.some((ur) => ur.role.name === 'ADMIN');

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function confirmDelete(user) {
    deleteTarget.value = user;
    showDeleteModal.value = true;
}

function destroyUser() {
    router.delete(`/admin/users/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            showDeleteModal.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>
