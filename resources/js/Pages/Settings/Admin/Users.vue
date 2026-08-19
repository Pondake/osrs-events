<template>
    <Head :title="$t('settings.nav_admin_users')" />

    <settings-layout current="admin-users">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-xl font-semibold text-highlighted">{{ $t('settings.nav_admin_users') }}</h2>
                <p class="text-sm text-muted mt-0.5">{{ $t('admin.users_subtitle', { count: users.length }) }}</p>
            </div>
            <u-input
                v-model="search"
                :placeholder="$t('admin.search_users_placeholder')"
                icon="i-lucide-search"
                class="w-full sm:w-64"
                @update:model-value="doSearch"
            />
        </div>

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
                        v-for="ur in u.user_roles"
                        :key="ur.id"
                        :label="ur.role.name"
                        :color="roleColor(ur.role.name)"
                        variant="subtle"
                    />
                    <u-badge
                        v-for="p in u.user_permissions"
                        :key="p.id"
                        :label="p.permission_key"
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
    </settings-layout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const props = defineProps({
    users: { type: Array, required: true },
    search: { type: String, default: '' },
    permissionKeys: { type: Array, required: true },
});

const { user: currentUser } = useAuth();

// Not fetched from the server — ADMIN/EDITOR/TEAM_MANAGER were the roles
// referenced across the old frontend's isAdmin/isEditor checks. Roles are
// otherwise freeform strings (Role::firstOrCreate in the controller), so
// this is the known set worth offering, not a hard constraint.
const ROLE_OPTIONS = ['ADMIN', 'EDITOR', 'TEAM_MANAGER', 'PLAYER'];
const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', TEAM_MANAGER: 'info', PLAYER: 'primary' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';

const displayName = (u) => u.nickname ?? u.discord_username ?? u.email;

const search = ref(props.search);

function doSearch(value) {
    router.get('/settings/admin/users', { search: value }, { preserveState: true, replace: true });
}

/**
 * Roles can be granted and revoked; permissions are grant-only by design —
 * revoking one is deliberately not offered here (see the backlog note).
 * Mirrors AdminUserController::destroy()'s refusals for the delete entry.
 */
function menuFor(u) {
    const held = u.user_roles.map((ur) => ur.role.name);
    const heldPerms = u.user_permissions.map((p) => p.permission_key);
    const isSelf = u.id === currentUser.value?.id;

    const groups = [];

    const grantableRoles = ROLE_OPTIONS.filter((r) => !held.includes(r));
    if (grantableRoles.length) {
        groups.push(grantableRoles.map((role) => ({
            label: trans('admin.assign_role', { role }),
            icon: 'i-lucide-plus',
            onSelect: () => router.post(`/settings/admin/users/${u.id}/roles`, { role }, { preserveScroll: true }),
        })));
    }

    const revocableRoles = u.user_roles.filter((ur) => !(isSelf && ur.role.name === 'ADMIN'));
    if (revocableRoles.length) {
        groups.push(revocableRoles.map((ur) => ({
            label: trans('admin.remove_role', { role: ur.role.name }),
            icon: 'i-lucide-minus',
            onSelect: () => router.delete(`/settings/admin/users/${u.id}/roles/${ur.role.id}`, { preserveScroll: true }),
        })));
    }

    const grantablePerms = props.permissionKeys.filter((k) => !heldPerms.includes(k));
    if (grantablePerms.length) {
        groups.push(grantablePerms.map((key) => ({
            label: trans('admin.grant_permission', { key }),
            icon: 'i-lucide-key',
            onSelect: () => router.post(`/settings/admin/users/${u.id}/permissions`, { permission_key: key }, { preserveScroll: true }),
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
    router.delete(`/settings/admin/users/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            showDeleteModal.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>
