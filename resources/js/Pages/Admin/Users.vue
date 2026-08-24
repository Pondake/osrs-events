<template>
    <Head :title="$t('settings.nav_admin_users')" />

    <admin-layout current="users" :title="$t('settings.nav_admin_users')" :description="$t('admin.users_subtitle')">
        <template #actions>
            <u-input
                v-model="search"
                :placeholder="$t('admin.search_users_placeholder')"
                icon="i-lucide-search"
                class="w-full sm:w-64"
                @update:model-value="doSearch"
            />
        </template>

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="u in users" :key="u.id" class="flex items-center justify-between gap-3 px-4 py-3 flex-wrap">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <u-avatar :src="u.avatar_url ?? undefined" :alt="displayName(u)" size="sm" />
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ displayName(u) }}</div>
                        <div class="text-xs text-muted truncate">{{ u.discord_username ? `@${u.discord_username}` : u.email }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                    <u-badge
                        v-for="role in u.roles"
                        :key="role.id"
                        :label="role.name"
                        :color="roleColor(role.name)"
                        variant="subtle"
                    />
                    <u-badge
                        v-for="permission in u.permissions"
                        :key="permission.id"
                        :label="permission.name"
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-key"
                    />

                    <!-- One menu per user instead of two always-visible selects
                         plus a delete button — the row was a wall of controls
                         before, and role/permission edits are occasional. -->
                    <u-dropdown-menu :items="menuFor(u)">
                        <u-button icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs" :aria-label="$t('common.edit')" />
                    </u-dropdown-menu>
                </div>
            </div>
            <p v-if="!users.length" class="px-4 py-10 text-center text-muted text-sm">{{ $t('admin.no_users') }}</p>
        </div>

        <client-only>
            <u-modal v-model:open="showDeleteModal" :title="$t('admin.delete_user')">
                <template #body>
                    <p class="text-muted">{{ $t('admin.delete_user_confirm', { name: deleteTarget ? displayName(deleteTarget) : '' }) }}</p>
                </template>
                <template #footer>
                    <div class="flex gap-2 justify-end w-full">
                        <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="showDeleteModal = false" />
                        <u-button color="error" icon="i-lucide-trash-2" :label="$t('admin.delete_user')" @click="destroyUser" />
                    </div>
                </template>
            </u-modal>
        </client-only>
    </admin-layout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import AdminLayout from '@/Components/AdminLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const props = defineProps({
    users: { type: Array, required: true },
    search: { type: String, default: '' },
    permissionKeys: { type: Array, required: true },
});

const { user: currentUser } = useAuth();

// Not fetched from the server — these are the roles the app itself checks
// for. Roles are otherwise freeform strings (Role::firstOrCreate in the
// controller), so this is the known set worth offering, not a hard
// constraint. TEAM_MANAGER used to be here and was retired: it granted
// management over every team on the site, which the per-team OWNER/MANAGER
// roles replaced. Offering it again would recreate it.
const ROLE_OPTIONS = ['ADMIN', 'EDITOR', 'PLAYER'];
const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', PLAYER: 'primary' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';

const displayName = (u) => u.nickname ?? u.discord_username ?? u.email;

const search = ref(props.search);

function doSearch(value) {
    router.get('/admin/users', { search: value }, { preserveState: true, replace: true });
}

/**
 * Roles can be granted and revoked; permissions are grant-only by design —
 * revoking one is deliberately not offered here (see the backlog note).
 * Mirrors AdminUserController::destroy()'s refusals for the delete entry.
 */
function menuFor(u) {
    const held = u.roles.map((role) => role.name);
    const heldPerms = u.permissions.map((permission) => permission.name);
    const isSelf = u.id === currentUser.value?.id;

    const groups = [];

    const grantableRoles = ROLE_OPTIONS.filter((r) => !held.includes(r));
    if (grantableRoles.length) {
        groups.push(grantableRoles.map((role) => ({
            label: trans('admin.assign_role', { role }),
            icon: 'i-lucide-plus',
            onSelect: () => router.post(`/admin/users/${u.id}/roles`, { role }, { preserveScroll: true }),
        })));
    }

    const revocableRoles = u.roles.filter((role) => !(isSelf && role.name === 'ADMIN'));
    if (revocableRoles.length) {
        groups.push(revocableRoles.map((role) => ({
            label: trans('admin.remove_role', { role: role.name }),
            icon: 'i-lucide-minus',
            onSelect: () => router.delete(`/admin/users/${u.id}/roles/${role.id}`, { preserveScroll: true }),
        })));
    }

    const grantablePerms = props.permissionKeys.filter((k) => !heldPerms.includes(k));
    if (grantablePerms.length) {
        groups.push(grantablePerms.map((key) => ({
            label: trans('admin.grant_permission', { key }),
            icon: 'i-lucide-key',
            onSelect: () => router.post(`/admin/users/${u.id}/permissions`, { permission_key: key }, { preserveScroll: true }),
        })));
    }

    if (!isSelf && !held.includes('ADMIN')) {
        groups.push([{
            label: trans('admin.delete_user'),
            icon: 'i-lucide-trash-2',
            color: 'error',
            onSelect: () => confirmDelete(u),
        }]);
    }

    return groups;
}

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function confirmDelete(u) {
    deleteTarget.value = u;
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
