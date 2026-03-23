<template>
  <nuxt-layout :title="$t('admin.teams_title')" :description="$t('admin.teams_subtitle')">
    <u-page-body>
      <u-container class="max-w-3xl">
        <!-- Loading -->
        <div v-if="loading" class="flex flex-col gap-3 mt-4">
          <u-skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
        </div>

        <!-- Empty state -->
        <div v-else-if="teams.length === 0" class="text-center py-16">
          <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />
          <p class="text-lg font-medium osrs-font">{{ $t('teams.no_teams') }}</p>
          <p class="text-sm text-muted mt-2">{{ $t('teams.no_teams_desc') }}</p>
        </div>

        <!-- Team list -->
        <div v-else class="flex flex-col gap-4 mt-4">
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

              <!-- Delete team -->
              <u-button
                variant="ghost"
                color="error"
                size="xs"
                icon="i-lucide-trash"
                :aria-label="$t('common.delete')"
                @click="openDelete(team)"
              />
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
                <span class="text-xs">{{
                  member.user.nickname ?? member.user.discordUsername
                }}</span>

                <!-- Remove member -->
                <button
                  type="button"
                  class="text-muted hover:text-error transition-colors ml-0.5"
                  :title="$t('teams.remove_member')"
                  @click="removeMember(team.id, member.userId)"
                >
                  <u-icon name="i-lucide-x" class="text-xs" />
                </button>
              </div>
            </div>

            <p v-else class="text-xs text-muted italic">
              {{ $t('teams.no_members') }}
            </p>
          </div>
        </div>
      </u-container>
    </u-page-body>

    <!-- Delete confirm modal -->
    <u-modal
      v-model:open="showDeleteConfirm"
      :title="$t('teams.delete_confirm_title')"
    >
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
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAllTeams, type TeamData } from '~/composables/useTeams'

definePageMeta({ middleware: 'admin' })

const toast = useToast()
const { t } = useI18n()

const { teams, loading, load, deleteTeam, removeTeamMember } = useAllTeams()

onMounted(load)

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

// ─── Remove member ────────────────────────────────────────────────────────────

async function removeMember(teamId: string, userId: string) {
  try {
    await removeTeamMember(teamId, userId)
    const team = teams.value.find(t => t.id === teamId)
    if (team) team.members = team.members.filter(m => m.userId !== userId)
    toast.add({ title: t('teams.member_removed'), color: 'neutral' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  }
}
</script>
