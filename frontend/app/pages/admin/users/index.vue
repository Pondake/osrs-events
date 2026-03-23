<template>
  <nuxt-layout :title="$t('admin.users_title')" :description="$t('admin.users_subtitle')">
    <u-page-body>
      <u-container>
        <!-- Search -->
        <div class="mb-6">
          <u-input
            v-model="searchQuery"
            :placeholder="$t('common.search')"
            icon="i-lucide-search"
            class="max-w-sm"
          />
        </div>

        <!-- Loading -->
        <div v-if="pending" class="flex flex-col gap-2">
          <u-skeleton v-for="i in 6" :key="i" class="h-14 rounded-xl" />
        </div>

        <!-- Error -->
        <u-alert
          v-else-if="error"
          color="error"
          icon="i-lucide-alert-circle"
          :title="$t('errors.generic')"
        />

        <!-- Table -->
        <u-table
          v-else-if="users.length > 0"
          :data="users"
          :columns="tableColumns"
        >
          <template #user-cell="{ row }">
            <div class="flex items-center gap-3">
              <u-avatar
                :src="row.original.avatarUrl ?? undefined"
                :alt="row.original.nickname ?? row.original.discordUsername"
                size="sm"
              />
              <div class="min-w-0">
                <p class="font-medium truncate">{{ row.original.nickname ?? row.original.discordUsername }}</p>
                <p v-if="row.original.nickname" class="text-xs text-muted truncate">
                  {{ row.original.discordUsername }}
                </p>
              </div>
            </div>
          </template>

          <template #joined-cell="{ row }">
            <span class="text-sm text-muted">{{ formatDate(row.original.createdAt) }}</span>
          </template>

          <template #roles-cell="{ row }">
            <div class="flex flex-wrap gap-1">
              <u-badge
                v-for="ur in (row.original.userRoles ?? [])"
                :key="ur.id"
                :color="roleColor(ur.role.name)"
                variant="subtle"
                size="md"
                :label="ur.role.name"
              />
              <span v-if="!row.original.userRoles?.length" class="text-xs text-muted italic">
                {{ $t('admin.no_roles') }}
              </span>
            </div>
          </template>

          <template #actions-cell="{ row }">
            <div class="flex justify-end gap-1">
              <u-button
                size="xs"
                variant="ghost"
                color="neutral"
                icon="i-lucide-shield"
                :aria-label="$t('admin.edit_roles')"
                @click="openRolesModal(row.original)"
              />
              <u-button
                size="xs"
                variant="ghost"
                color="neutral"
                icon="i-lucide-key"
                :aria-label="$t('admin.edit_permissions')"
                @click="openPermissionsModal(row.original)"
              />
              <u-button
                v-if="row.original.id !== authStore.user?.id && !row.original.userRoles?.some((ur: any) => ur.role.name === 'ADMIN')"
                size="xs"
                variant="ghost"
                color="error"
                icon="i-lucide-trash-2"
                :aria-label="$t('admin.delete_user')"
                @click="openDeleteModal(row.original)"
              />
            </div>
          </template>
        </u-table>

        <div v-else class="text-center py-12 text-muted">
          <u-icon name="i-lucide-users" class="text-5xl mb-3 block mx-auto" />
          <p>{{ $t('admin.no_users') }}</p>
        </div>
      </u-container>
    </u-page-body>

    <!-- ── Roles modal ──────────────────────────────────────────────────────── -->
    <u-modal v-model:open="showRolesModal" :title="$t('admin.edit_roles')">
      <template #body>
        <div v-if="selectedUser" class="flex flex-col gap-4">
          <div class="flex items-center gap-3">
            <u-avatar
              :src="selectedUser.avatarUrl ?? undefined"
              :alt="selectedUser.nickname ?? selectedUser.discordUsername"
            />
            <p class="font-semibold">{{ selectedUser.nickname ?? selectedUser.discordUsername }}</p>
          </div>

          <div class="flex flex-col gap-2">
            <div
              v-for="role in availableRoles"
              :key="role.name"
              class="flex items-center justify-between p-3 rounded-lg border border-default"
            >
              <div>
                <p class="text-sm font-medium">{{ role.label }}</p>
                <p class="text-xs text-muted">{{ role.description }}</p>
              </div>
              <u-switch
                :model-value="hasRole(selectedUser, role.name)"
                :disabled="role.name === 'ADMIN'"
                :loading="roleLoading === role.name"
                @update:model-value="toggleRole(selectedUser, role.name, $event)"
              />
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <u-button variant="ghost" color="neutral" :label="$t('common.close')" @click="showRolesModal = false" />
      </template>
    </u-modal>

    <!-- ── Permissions modal ────────────────────────────────────────────────── -->
    <u-modal v-model:open="showPermissionsModal" :title="$t('admin.edit_permissions')">
      <template #body>
        <div v-if="selectedUser" class="flex flex-col gap-4">
          <div class="flex items-center gap-3">
            <u-avatar
              :src="selectedUser.avatarUrl ?? undefined"
              :alt="selectedUser.nickname ?? selectedUser.discordUsername"
            />
            <p class="font-semibold">{{ selectedUser.nickname ?? selectedUser.discordUsername }}</p>
          </div>

          <div v-if="permissionsLoading" class="flex flex-col gap-2">
            <u-skeleton v-for="i in 2" :key="i" class="h-14 rounded-lg" />
          </div>

          <div v-else class="flex flex-col gap-2">
            <div
              v-for="perm in permissionDefs"
              :key="perm.key"
              class="flex items-center justify-between p-3 rounded-lg border border-default"
            >
              <div>
                <p class="text-sm font-medium">{{ perm.label }}</p>
                <p class="text-xs text-muted">{{ perm.description }}</p>
              </div>
              <u-switch
                :model-value="userHasPermission(selectedUser.id, perm.key)"
                :loading="permToggleLoading === perm.key"
                @update:model-value="togglePermission(selectedUser.id, perm.key, $event)"
              />
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <u-button variant="ghost" color="neutral" :label="$t('common.close')" @click="showPermissionsModal = false" />
      </template>
    </u-modal>
    <!-- ── Delete user modal ─────────────────────────────────────────────────── -->
    <u-modal v-model:open="showDeleteModal" :title="$t('admin.delete_user')">
      <template #body>
        <div v-if="userToDelete" class="flex flex-col gap-4">
          <div class="flex items-center gap-3">
            <u-avatar
              :src="userToDelete.avatarUrl ?? undefined"
              :alt="userToDelete.nickname ?? userToDelete.discordUsername"
            />
            <p class="font-semibold">{{ userToDelete.nickname ?? userToDelete.discordUsername }}</p>
          </div>
          <u-alert
            color="error"
            variant="subtle"
            icon="i-lucide-triangle-alert"
            :description="$t('admin.delete_user_confirm', { name: userToDelete.nickname ?? userToDelete.discordUsername })"
          />
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <u-button variant="ghost" color="neutral" :label="$t('common.cancel')" @click="showDeleteModal = false" />
          <u-button
            color="error"
            :label="$t('common.delete')"
            :loading="deleteLoading"
            @click="confirmDelete"
          />
        </div>
      </template>
    </u-modal>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { type UserEntity, PermissionKey } from '~/types/graphql'
import { useUsers, fetchUserPermissions, grantPermission, revokePermission, deleteUser as deleteUserMutation } from '~/composables/useUsers'
import { useAuthStore } from '~/stores/auth'
import { formatDate } from '~/utils/board'

definePageMeta({ middleware: 'admin' })

const { t } = useI18n()
const authStore = useAuthStore()
const toast = useToast()

// ─── Search + data ────────────────────────────────────────────────────────────

const searchQuery = ref('')
const debouncedSearch = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, val => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { debouncedSearch.value = val }, 350)
})

const { users, pending, error, refresh: refreshUsers, assignRole, removeRole } = await useUsers(computed(() => debouncedSearch.value || undefined))

// ─── Table columns ────────────────────────────────────────────────────────────

const tableColumns = computed(() => [
  { id: 'user',    header: t('admin.col_user') },
  { id: 'joined',  header: t('admin.col_joined') },
  { id: 'roles',   header: t('admin.col_roles') },
  { id: 'actions', header: '' },
])

// ─── Roles ────────────────────────────────────────────────────────────────────

const availableRoles = computed(() => [
  { name: 'ADMIN',        label: t('admin.role_label_admin'),        description: t('admin.role_desc_admin') },
  { name: 'EDITOR',       label: t('admin.role_label_editor'),       description: t('admin.role_desc_editor') },
  { name: 'TEAM_MANAGER', label: t('admin.role_label_team_manager'), description: t('admin.role_desc_team_manager') },
])

function roleColor(name: string): 'primary' | 'warning' | 'info' | 'neutral' {
  const map: Record<string, 'primary' | 'warning' | 'info' | 'neutral'> = {
    ADMIN: 'primary',
    EDITOR: 'warning',
    TEAM_MANAGER: 'info',
  }
  return map[name] ?? 'neutral'
}

function hasRole(user: UserEntity, role: string): boolean {
  return user.userRoles.some(ur => ur.role.name === role)
}

const roleLoading = ref<string | null>(null)

async function toggleRole(user: UserEntity, role: string, value: boolean) {
  if (role === 'ADMIN') return
  roleLoading.value = role
  try {
    const updated = value
      ? await assignRole(user.id, role)
      : await removeRole(user.id, role)
    const idx = users.value.findIndex(u => u.id === user.id)
    if (idx >= 0) users.value[idx].userRoles = updated.userRoles
    toast.add({
      title: value ? t('admin.role_assigned', { role }) : t('admin.role_removed', { role }),
      color: value ? 'success' : 'neutral',
    })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    roleLoading.value = null
  }
}

// ─── Roles modal ──────────────────────────────────────────────────────────────

const showRolesModal = ref(false)
const selectedUser = ref<UserEntity | null>(null)

function openRolesModal(user: UserEntity) {
  selectedUser.value = user
  showRolesModal.value = true
}

// ─── Permissions ──────────────────────────────────────────────────────────────

const permissionDefs = computed(() => [
  { key: PermissionKey.CanCreateBoards, label: t('admin.perm_label_can_create_boards'), description: t('admin.perm_desc_can_create_boards') },
  { key: PermissionKey.CanCreateTiles,  label: t('admin.perm_label_can_create_tiles'),  description: t('admin.perm_desc_can_create_tiles') },
])

const userPermissionsMap = ref<Record<string, string[]>>({})
const permissionsLoading = ref(false)
const permToggleLoading = ref<string | null>(null)

const showPermissionsModal = ref(false)

async function openPermissionsModal(user: UserEntity) {
  selectedUser.value = user
  showPermissionsModal.value = true
  if (userPermissionsMap.value[user.id] !== undefined) return
  permissionsLoading.value = true
  try {
    userPermissionsMap.value[user.id] = await fetchUserPermissions(user.id)
  } catch {
    userPermissionsMap.value[user.id] = []
  } finally {
    permissionsLoading.value = false
  }
}

function userHasPermission(userId: string, key: string): boolean {
  return (userPermissionsMap.value[userId] ?? []).includes(key)
}

// ─── Delete user ──────────────────────────────────────────────────────────────

const showDeleteModal = ref(false)
const userToDelete = ref<UserEntity | null>(null)
const deleteLoading = ref(false)

function openDeleteModal(user: UserEntity) {
  userToDelete.value = user
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!userToDelete.value) return
  deleteLoading.value = true
  try {
    await deleteUserMutation(userToDelete.value.id)
    const name = userToDelete.value.nickname ?? userToDelete.value.discordUsername
    await refreshUsers()
    toast.add({ title: t('admin.user_deleted', { name }), color: 'success' })
    showDeleteModal.value = false
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    deleteLoading.value = false
  }
}

async function togglePermission(userId: string, key: string, value: boolean) {
  permToggleLoading.value = key
  try {
    if (value) {
      await grantPermission(userId, key)
      userPermissionsMap.value[userId] = [...(userPermissionsMap.value[userId] ?? []), key]
      toast.add({ title: t('admin.perm_granted', { key }), color: 'success' })
    } else {
      await revokePermission(userId, key)
      userPermissionsMap.value[userId] = (userPermissionsMap.value[userId] ?? []).filter(k => k !== key)
      toast.add({ title: t('admin.perm_revoked', { key }), color: 'neutral' })
    }
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    permToggleLoading.value = null
  }
}
</script>
