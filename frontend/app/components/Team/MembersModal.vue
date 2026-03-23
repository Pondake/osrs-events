<template>
  <u-modal v-model:open="proxyOpen" :title="$t('teams.manage_members', { name: currentTeam?.name })">
    <template #body>
      <div class="flex flex-col gap-4">

        <!-- Current members -->
        <div>
          <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">
            {{ $t('teams.members') }}
          </p>

          <div v-if="currentTeam && currentTeam.members.length > 0" class="flex flex-col gap-1">
            <div
              v-for="member in currentTeam.members"
              :key="member.id"
              class="flex items-center gap-2 px-3 py-2 rounded-lg bg-muted/20"
            >
              <u-avatar
                :src="member.user.avatarUrl ?? undefined"
                :alt="member.user.nickname ?? member.user.discordUsername"
                size="xs"
              />

              <span class="text-sm flex-1">{{ member.user.nickname ?? member.user.discordUsername }}</span>

              <u-button
                variant="ghost"
                color="error"
                size="2xs"
                icon="i-lucide-x"
                :loading="removingMemberId === member.userId"
                :disabled="!!removingMemberId && removingMemberId !== member.userId"
                @click="handleRemove(member.userId)"
              />
            </div>
          </div>

          <p v-else class="text-xs text-muted italic px-1">
            {{ $t('teams.no_members') }}
          </p>
        </div>

        <!-- Divider -->
        <u-separator />

        <!-- Add member -->
        <div>
          <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">
            {{ $t('teams.add_member') }}
          </p>

          <u-input
            v-model="memberSearch"
            :placeholder="$t('common.search')"
            icon="i-lucide-search"
            class="w-full mb-2"
          />

          <div class="flex flex-col gap-1 max-h-52 overflow-y-auto">
            <div
              v-for="user in displayedUsers"
              :key="user.id"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-muted/30 cursor-pointer transition-colors"
              :class="{ 'opacity-50 pointer-events-none': addingMemberId === user.id }"
              @click="handleAdd(user.id)"
            >
              <u-avatar
                :src="user.avatarUrl ?? undefined"
                :alt="user.nickname ?? user.discordUsername"
                size="xs"
              />

              <span class="text-sm flex-1">{{ user.nickname ?? user.discordUsername }}</span>

              <u-icon
                v-if="addingMemberId === user.id"
                name="i-lucide-loader-circle"
                class="text-sm animate-spin text-muted"
              />
            </div>

            <p
              v-if="displayedUsers.length === 0 && memberSearch.length >= 2"
              class="text-sm text-muted text-center py-3"
            >
              {{ $t('teams.no_users_found') }}
            </p>

            <p
              v-else-if="displayedUsers.length === 0 && loadingRecent"
              class="text-sm text-muted text-center py-3"
            >
              {{ $t('common.loading') }}
            </p>
          </div>
        </div>

      </div>
    </template>

    <template #footer>
      <u-button
        variant="ghost"
        color="neutral"
        :label="$t('common.close')"
        @click="proxyOpen = false"
      />
    </template>
  </u-modal>
</template>

<script setup lang="ts">
import type { TeamData } from '~/composables/useTeams'
import { fetchUsers, fetchRecentUsers } from '~/composables/useUsers'
import type { UserEntity } from '~/types/graphql'

// ─── Props / emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  open: boolean
  team: TeamData | null
  /** Called when the user picks someone from the list — must return the updated team */
  addMember: (teamId: string, userId: string) => Promise<TeamData>
  /** Called when the user removes a member — returns void; component updates locally */
  removeMember: (teamId: string, userId: string) => Promise<void>
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  /** Emitted after every successful add or remove with the latest team snapshot */
  'team-updated': [team: TeamData]
}>()

// ─── State ────────────────────────────────────────────────────────────────────

const toast = useToast()
const { t } = useI18n()

const proxyOpen = computed({
  get: () => props.open,
  set: (v) => emit('update:open', v),
})

// Local copy of the team so the list updates instantly without waiting for the parent prop
const currentTeam = ref<TeamData | null>(null)

const memberSearch = ref('')
const addingMemberId = ref<string | null>(null)
const removingMemberId = ref<string | null>(null)
const allUsers = ref<UserEntity[]>([])
const loadingRecent = ref(false)

const displayedUsers = computed(() => {
  const existing = new Set(currentTeam.value?.members.map(m => m.userId) ?? [])
  return allUsers.value.filter(u => !existing.has(u.id))
})

// ─── Lifecycle ────────────────────────────────────────────────────────────────

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    // Snapshot team on open
    currentTeam.value = props.team
    memberSearch.value = ''
    allUsers.value = []
    // Pre-load 5 most recently joined users
    loadingRecent.value = true
    try {
      allUsers.value = await fetchRecentUsers(5)
    } catch {
      allUsers.value = []
    } finally {
      loadingRecent.value = false
    }
  },
)

watch(memberSearch, async (val) => {
  if (val.length >= 2) {
    try {
      allUsers.value = await fetchUsers(val)
    } catch {
      allUsers.value = []
    }
  } else if (val.length === 0) {
    // Restore recent users when search is cleared
    try {
      allUsers.value = await fetchRecentUsers(5)
    } catch {
      allUsers.value = []
    }
  }
})

// ─── Actions ──────────────────────────────────────────────────────────────────

async function handleAdd(userId: string) {
  if (!currentTeam.value || addingMemberId.value) return
  addingMemberId.value = userId
  try {
    const updated = await props.addMember(currentTeam.value.id, userId)
    currentTeam.value = updated
    emit('team-updated', updated)
    toast.add({ title: t('teams.member_added'), color: 'success' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    addingMemberId.value = null
  }
}

async function handleRemove(userId: string) {
  if (!currentTeam.value || removingMemberId.value) return
  removingMemberId.value = userId
  try {
    await props.removeMember(currentTeam.value.id, userId)
    const updated: TeamData = {
      ...currentTeam.value,
      members: currentTeam.value.members.filter(m => m.userId !== userId),
    }
    currentTeam.value = updated
    emit('team-updated', updated)
    toast.add({ title: t('teams.member_removed'), color: 'neutral' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    removingMemberId.value = null
  }
}
</script>
