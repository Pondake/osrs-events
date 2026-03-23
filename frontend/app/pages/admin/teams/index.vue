<template>
  <nuxt-layout :title="$t('admin.teams_title')" :description="$t('admin.teams_subtitle')">
    <template #links>
      <u-button
        color="primary"
        icon="i-lucide-plus"
        size="sm"
        :label="$t('teams.create_team')"
        @click="openCreate"
      />
    </template>

    <u-page-body>
      <u-container class="max-w-3xl">

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

        <!-- Manage members modal (shared component) -->
        <team-members-modal
          v-model:open="showMembers"
          :team="managingTeam"
          :add-member="addTeamMember"
          :remove-member="removeTeamMember"
          @team-updated="onTeamUpdated"
        />

        <!-- Delete confirm modal -->
        <u-modal v-model:open="showDeleteConfirm" :title="$t('teams.delete_confirm_title')">
          <template #body>
            <p class="text-sm text-muted">
              {{ $t('teams.delete_confirm_body', { name: deletingTeam?.name }) }}
            </p>
          </template>

          <template #footer>
            <div class="flex gap-2 justify-end">
              <u-button
                variant="ghost"
                color="neutral"
                :label="$t('common.cancel')"
                @click="showDeleteConfirm = false"
              />
              <u-button
                color="error"
                :loading="deleting"
                :label="$t('common.delete')"
                @click="confirmDelete"
              />
            </div>
          </template>
        </u-modal>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col gap-3">
          <u-skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
        </div>

        <!-- Empty state -->
        <div v-else-if="teams.length === 0" class="text-center py-16">
          <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />
          <p class="text-lg font-medium osrs-font">{{ $t('teams.no_teams') }}</p>
          <p class="text-sm text-muted mt-2">{{ $t('teams.no_teams_desc') }}</p>
        </div>

        <!-- Team list -->
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

              <!-- Action buttons -->
              <div class="flex gap-1">
                <u-button
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-lucide-users"
                  :label="$t('teams.manage_members_short')"
                  @click="openMembers(team)"
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
                  :aria-label="$t('common.delete')"
                  @click="openDelete(team)"
                />
              </div>
            </div>

            <!-- Members preview -->
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
              </div>
            </div>

            <p v-else class="text-xs text-muted italic">
              {{ $t('teams.no_members') }}
            </p>
          </div>
        </div>

      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAllTeams, type TeamData } from '~/composables/useTeams'
import type { TeamFormData } from '~/components/Team/Form.vue'

definePageMeta({ middleware: 'admin' })

const toast = useToast()
const { t } = useI18n()

const { teams, loading, load, createTeam, updateTeam, deleteTeam, addTeamMember, removeTeamMember } =
  useAllTeams()

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

// ─── Delete team ──────────────────────────────────────────────────────────────

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

// ─── Members modal ────────────────────────────────────────────────────────────

const showMembers = ref(false)
const managingTeam = ref<TeamData | null>(null)

function openMembers(team: TeamData) {
  managingTeam.value = team
  showMembers.value = true
}

function onTeamUpdated(updated: TeamData) {
  const idx = teams.value.findIndex(t => t.id === updated.id)
  if (idx >= 0) teams.value[idx] = updated
  managingTeam.value = updated
}
</script>
