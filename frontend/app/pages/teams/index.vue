<template>
  <nuxt-layout :title="$t('teams.title')">
    <u-page-body>
      <u-container class="max-w-3xl">
        <!-- Access guard -->
        <u-alert
          v-if="!authStore.isAuthenticated"
          color="warning"
          icon="i-lucide-lock"
          :title="$t('errors.unauthorized')"
          class="my-4"
        />

        <template v-else>
          <!-- Create / Edit team modal -->
          <u-modal v-model:open="showForm" :title="editingTeam ? $t('teams.edit_team') : $t('teams.create_team')">
            <template #body>
              <team-form v-model="formData" />
            </template>

            <template #footer>
              <div class="flex gap-2 justify-end">
                <u-button variant="ghost" color="neutral" :label="$t('common.cancel')" @click="closeForm" />

                <u-button
                  color="primary"
                  :loading="saving"
                  :disabled="!formData.name.trim()"
                  :label="editingTeam ? $t('common.save') : $t('common.create')"
                  @click="submitForm"
                />
              </div>
            </template>
          </u-modal>

          <!-- Add member modal -->
          <u-modal v-model:open="showAddMember" :title="$t('teams.add_member')">
            <template #body>
              <div class="flex flex-col gap-3">
                <u-input
                  v-model="memberSearch"
                  :placeholder="$t('common.search')"
                  icon="i-lucide-search"
                  class="w-full"
                />

                <!-- Search results -->
                <div v-if="memberSearch.length >= 2" class="flex flex-col gap-1 max-h-64 overflow-y-auto">
                  <div
                    v-for="user in filteredUsers"
                    :key="user.id"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-muted/30 cursor-pointer"
                    @click="addMember(user.id)"
                  >
                    <u-avatar
                      :src="user.avatarUrl ?? undefined"
                      :alt="user.nickname ?? user.discordUsername"
                      size="xs"
                    />

                    <span class="text-sm">{{ user.nickname ?? user.discordUsername }}</span>
                  </div>

                  <p v-if="filteredUsers.length === 0" class="text-sm text-muted text-center py-4">
                    {{ $t('teams.no_users_found') }}
                  </p>
                </div>
              </div>
            </template>

            <template #footer>
              <u-button variant="ghost" color="neutral" :label="$t('common.close')" @click="showAddMember = false" />
            </template>
          </u-modal>

          <!-- Delete confirm modal -->
          <u-modal v-model:open="showDeleteConfirm" :title="$t('teams.delete_confirm_title')">
            <template #body>
              <p class="text-sm text-muted">{{ $t('teams.delete_confirm_body', { name: deletingTeam?.name }) }}</p>
            </template>

            <template #footer>
              <div class="flex gap-2 justify-end">
                <u-button variant="ghost" color="neutral" :label="$t('common.cancel')" @click="showDeleteConfirm = false" />
                <u-button color="error" :loading="deleting" :label="$t('common.delete')" @click="confirmDelete" />
              </div>
            </template>
          </u-modal>

          <!-- Header row -->
          <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-muted">{{ $t('teams.my_teams_subtitle') }}</p>

            <u-button
              color="primary"
              icon="i-lucide-plus"
              size="sm"
              :label="$t('teams.create_team')"
              @click="openCreate"
            />
          </div>

          <!-- Loading -->
          <div v-if="loading" class="flex flex-col gap-3">
            <u-skeleton v-for="i in 3" :key="i" class="h-24 rounded-xl" />
          </div>

          <!-- Empty -->
          <div v-else-if="teams.length === 0" class="text-center py-16">
            <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />

            <p class="text-lg font-medium osrs-font">{{ $t('teams.no_teams') }}</p>

            <p class="text-sm text-muted mt-2 mb-6">{{ $t('teams.no_teams_desc') }}</p>

            <u-button color="primary" icon="i-lucide-plus" :label="$t('teams.create_team')" @click="openCreate" />
          </div>

          <!-- Team cards -->
          <div v-else class="flex flex-col gap-4">
            <div
              v-for="team in teams"
              :key="team.id"
              class="p-4 bg-muted/20 rounded-xl border border-default"
            >
              <!-- Team header -->
              <div class="flex items-center gap-3 mb-3">
                <div
                  class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg bg-muted border border-default overflow-hidden"
                >
                  <img
                    v-if="team.iconUrl"
                    :src="team.iconUrl"
                    :alt="team.name"
                    class="w-9 h-9 object-contain image-rendering-pixelated"
                  />

                  <u-icon v-else name="i-lucide-users" class="text-lg text-muted" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm">{{ team.name }}</p>

                  <p class="text-xs text-muted">
                    {{ $t('teams.member_count', { count: team.members.length }) }}
                  </p>
                </div>

                <!-- Actions (visible to managers/admins of this team) -->
                <div v-if="canManageThisTeam(team)" class="flex gap-1">
                  <u-button
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-lucide-user-plus"
                    :label="$t('teams.add_member')"
                    @click="openAddMember(team)"
                  />

                  <u-button
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-lucide-pencil"
                    @click="openEdit(team)"
                  />

                  <u-button
                    variant="ghost"
                    color="error"
                    size="xs"
                    icon="i-lucide-trash"
                    @click="openDelete(team)"
                  />
                </div>
              </div>

              <!-- Members -->
              <div v-if="team.members.length > 0" class="flex flex-wrap gap-2">
                <div
                  v-for="member in team.members"
                  :key="member.id"
                  class="flex items-center gap-1.5 bg-muted/30 rounded-full pl-1 pr-2 py-0.5"
                >
                  <u-avatar
                    :src="member.user.avatarUrl ?? undefined"
                    :alt="member.user.nickname ?? member.user.discordUsername"
                    size="2xs"
                  />

                  <span class="text-xs">{{ member.user.nickname ?? member.user.discordUsername }}</span>

                  <!-- Remove member (managers/admins only) -->
                  <button
                    v-if="canManageThisTeam(team)"
                    type="button"
                    class="text-muted hover:text-error transition-colors ml-0.5"
                    :title="$t('teams.remove_member')"
                    @click="removeMember(team, member.userId)"
                  >
                    <u-icon name="i-lucide-x" class="text-xs" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useMyTeams, type TeamData, type TeamMemberData } from '~/composables/useTeams'
import { fetchUsers } from '~/composables/useUsers'
import type { UserEntity } from '~/types/graphql'
import type { TeamFormData } from '~/components/Team/Form.vue'

const authStore = useAuthStore()
const toast = useToast()
const { t } = useI18n()

const permissions = usePermissions()

const { teams, loading, load, createTeam, updateTeam, deleteTeam, addTeamMember, removeTeamMember } =
  useMyTeams()

onMounted(load)

// ─── Create / Edit ────────────────────────────────────────────────────────────

const showForm = ref(false)
const saving = ref(false)
const editingTeam = ref<TeamData | null>(null)
const formData = ref<TeamFormData>({ name: '', iconUrl: '' })

function openCreate() {
  editingTeam.value = null
  formData.value = { name: '', iconUrl: '' }
  showForm.value = true
}

function openEdit(team: TeamData) {
  editingTeam.value = team
  formData.value = { name: team.name, iconUrl: team.iconUrl ?? '' }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingTeam.value = null
}

async function submitForm() {
  if (!formData.value.name.trim()) return
  saving.value = true
  try {
    if (editingTeam.value) {
      const updated = await updateTeam(editingTeam.value.id, {
        name: formData.value.name.trim(),
        iconUrl: formData.value.iconUrl || null,
      })
      const idx = teams.value.findIndex(t => t.id === updated.id)
      if (idx >= 0) teams.value[idx] = updated
      toast.add({ title: t('teams.team_updated'), color: 'success' })
    } else {
      const created = await createTeam({
        name: formData.value.name.trim(),
        iconUrl: formData.value.iconUrl || null,
      })
      teams.value.push(created)
      toast.add({ title: t('teams.team_created'), color: 'success' })
    }
    closeForm()
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    saving.value = false
  }
}

// ─── Delete ────────────────────────────────────────────────────────────────────

const showDeleteConfirm = ref(false)
const deleting = ref(false)
const deletingTeam = ref<TeamData | null>(null)

function openDelete(team: TeamData) {
  deletingTeam.value = team
  showDeleteConfirm.value = true
}

async function confirmDelete() {
  if (!deletingTeam.value) return
  deleting.value = true
  try {
    await deleteTeam(deletingTeam.value.id)
    teams.value = teams.value.filter(t => t.id !== deletingTeam.value!.id)
    toast.add({ title: t('teams.team_deleted'), color: 'neutral' })
    showDeleteConfirm.value = false
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    deleting.value = false
  }
}

// ─── Add / remove members ────────────────────────────────────────────────────

const showAddMember = ref(false)
const addingToTeam = ref<TeamData | null>(null)
const memberSearch = ref('')

// Simple user search — imperative, uses fetchUsers from useUsers composable
const allUsers = ref<UserEntity[]>([])

async function loadUsers(search: string) {
  try {
    allUsers.value = await fetchUsers(search)
  } catch {
    allUsers.value = []
  }
}

const filteredUsers = computed(() => {
  const existing = new Set(addingToTeam.value?.members.map(m => m.userId) ?? [])
  return allUsers.value.filter(u => !existing.has(u.id))
})

watch(memberSearch, val => {
  if (val.length >= 2) loadUsers(val)
  else allUsers.value = []
})

function openAddMember(team: TeamData) {
  addingToTeam.value = team
  memberSearch.value = ''
  allUsers.value = []
  showAddMember.value = true
}

async function addMember(userId: string) {
  if (!addingToTeam.value) return
  try {
    const updatedTeam = await addTeamMember(addingToTeam.value.id, userId)
    const idx = teams.value.findIndex(t => t.id === addingToTeam.value!.id)
    if (idx >= 0) {
      teams.value[idx] = updatedTeam
      // Keep the modal open but update the reference so the member filter stays accurate
      addingToTeam.value = updatedTeam
    }
    toast.add({ title: t('teams.member_added'), color: 'success' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  }
}

async function removeMember(team: TeamData, userId: string) {
  try {
    await removeTeamMember(team.id, userId)
    const t2 = teams.value.find(t => t.id === team.id)
    if (t2) t2.members = t2.members.filter(m => m.userId !== userId)
    toast.add({ title: t('teams.member_removed'), color: 'neutral' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  }
}

// ─── Permission check ─────────────────────────────────────────────────────────

function canManageThisTeam(team: TeamData): boolean {
  return permissions.canManageTeam(team.members)
}
</script>
