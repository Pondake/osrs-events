<template>
  <div class="space-y-6">
    <!-- Editors -->
    <u-form-field
      :label="$t('admin.editors')"
      :description="$t('admin.editors_desc')"
      name="editors"
    >
      <div class="space-y-2">
        <div class="flex gap-2">
          <u-input
            v-model="authorSearch"
            :placeholder="$t('common.search')"
            class="flex-1"
            @input="onAuthorSearch"
          />
        </div>

        <div
          v-if="userResults.length > 0"
          class="border border-muted rounded-lg overflow-hidden"
        >
          <button
            v-for="user in userResults"
            :key="user.id"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2 hover:bg-muted transition-colors text-left"
            @click="addAuthor(user)"
          >
            <u-avatar :src="user.avatarUrl ?? undefined" :alt="user.discordUsername" size="xs" />
            <span>{{ user.discordUsername }}</span>
          </button>
        </div>

        <div v-if="modelValue.selectedAuthors.length > 0" class="flex flex-wrap gap-2 mt-2">
          <u-badge
            v-for="author in modelValue.selectedAuthors"
            :key="author.id"
            color="primary"
            variant="subtle"
            class="flex items-center gap-1"
          >
            {{ author.discordUsername }}
            <button
              v-if="author.id !== currentUserId"
              type="button"
              class="ml-1 hover:text-red-400"
              @click="removeAuthor(author.id)"
            >
              <u-icon name="i-lucide-x" class="w-3 h-3" />
            </button>
          </u-badge>
        </div>
      </div>
    </u-form-field>

    <!-- Team assignment — only visible for TEAM mode boards -->
    <template v-if="modelValue.mode === 'TEAM'">
      <u-separator />

      <u-form-field
        :label="$t('admin.team_assignment')"
        :description="$t('admin.team_assignment_desc')"
        name="teams"
      >
        <!-- Currently assigned teams -->
        <div v-if="modelValue.assignedTeams.length > 0" class="flex flex-wrap gap-2 mb-3">
          <div
            v-for="bt in modelValue.assignedTeams"
            :key="bt.teamId"
            class="flex items-center gap-2 px-2 py-1 bg-muted/30 rounded-lg border border-default"
          >
            <img
              v-if="bt.team.iconUrl"
              :src="bt.team.iconUrl"
              :alt="bt.team.name"
              class="size-5 object-contain"
              style="image-rendering: pixelated"
            />
            <u-icon v-else name="i-lucide-users" class="size-4 text-muted" />
            <span class="text-sm font-medium">{{ bt.team.name }}</span>
            <button
              type="button"
              class="ml-1 text-muted hover:text-red-400 transition-colors"
              @click="$emit('remove-team', bt.teamId)"
            >
              <u-icon name="i-lucide-x" class="size-3" />
            </button>
          </div>
        </div>

        <p v-else class="text-sm text-muted mb-3">{{ $t('admin.no_teams_assigned') }}</p>

        <!-- Add team search -->
        <div class="space-y-2">
          <div class="flex gap-2">
            <u-input
              v-model="teamSearch"
              :placeholder="$t('teams.team_name_placeholder')"
              class="flex-1"
              icon="i-lucide-search"
              @input="onTeamSearch"
            />
          </div>

          <div
            v-if="filteredTeams.length > 0"
            class="border border-muted rounded-lg overflow-hidden"
          >
            <button
              v-for="team in filteredTeams"
              :key="team.id"
              type="button"
              class="w-full flex items-center gap-3 px-3 py-2 hover:bg-muted transition-colors text-left"
              @click="$emit('add-team', { id: team.id, name: team.name, iconUrl: team.iconUrl ?? null })"
            >
              <img
                v-if="team.iconUrl"
                :src="team.iconUrl"
                :alt="team.name"
                class="size-5 object-contain"
                style="image-rendering: pixelated"
              />
              <u-icon v-else name="i-lucide-users" class="size-4 text-muted" />
              <span>{{ team.name }}</span>
            </button>
          </div>
        </div>
      </u-form-field>
    </template>

  </div>
</template>

<script setup lang="ts">
import { fetchUsers } from '~/composables/useUsers'
import { useAllTeams } from '~/composables/useTeams'
import type { BoardFormData, AuthorOption } from '~/components/Board/SettingsForm.vue'

const props = defineProps<{
  modelValue: BoardFormData
  currentUserId?: string
  boardId?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData]
  'add-team': [team: { id: string; name: string; iconUrl: string | null }]
  'remove-team': [teamId: string]
}>()

// ─── Author search ────────────────────────────────────────────────────────────

const authorSearch = ref('')
const userResults = ref<AuthorOption[]>([])
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onAuthorSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (authorSearch.value.length < 2) {
    userResults.value = []
    return
  }
  searchTimeout = setTimeout(async () => {
    try {
      const results = await fetchUsers(authorSearch.value)
      userResults.value = results.filter(
        u => !props.modelValue.selectedAuthors.some(a => a.id === u.id),
      )
    } catch {
      userResults.value = []
    }
  }, 300)
}

function addAuthor(user: AuthorOption) {
  if (!props.modelValue.selectedAuthors.some(a => a.id === user.id)) {
    emit('update:modelValue', {
      ...props.modelValue,
      selectedAuthors: [...props.modelValue.selectedAuthors, user],
    })
  }
  userResults.value = []
  authorSearch.value = ''
}

function removeAuthor(userId: string) {
  emit('update:modelValue', {
    ...props.modelValue,
    selectedAuthors: props.modelValue.selectedAuthors.filter(a => a.id !== userId),
  })
}

// ─── Team search ──────────────────────────────────────────────────────────────

const teamSearch = ref('')
const { teams: allTeams, load: loadTeams } = useAllTeams()

onMounted(() => {
  if (props.modelValue.mode === 'TEAM') {
    loadTeams()
  }
})

watch(() => props.modelValue.mode, (mode) => {
  if (mode === 'TEAM' && allTeams.value.length === 0) {
    loadTeams()
  }
})

const filteredTeams = computed(() => {
  const assignedIds = new Set(props.modelValue.assignedTeams.map(bt => bt.teamId))
  const q = teamSearch.value.toLowerCase()
  return allTeams.value.filter(t =>
    !assignedIds.has(t.id) && (!q || t.name.toLowerCase().includes(q))
  )
})

function onTeamSearch() {
  // Filtering is done via computed — just needed to trigger reactivity on input
}
</script>
