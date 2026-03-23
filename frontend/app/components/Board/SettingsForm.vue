<template>
  <div class="space-y-6">
    <!-- Title -->
    <u-form-field
      :label="$t('admin.board_title')"
      :description="$t('admin.board_title_desc')"
      name="title"
      required
    >
      <u-input
        :model-value="modelValue.title"
        :placeholder="$t('admin.board_title_placeholder')"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, title: $event })"
      />
    </u-form-field>

    <!-- Description -->
    <u-form-field
      :label="$t('admin.board_description')"
      :description="$t('admin.board_description_desc')"
      name="description"
    >
      <u-textarea
        :model-value="modelValue.description"
        :placeholder="$t('admin.board_description_placeholder')"
        class="w-full"
        :rows="3"
        @update:model-value="emit('update:modelValue', { ...modelValue, description: $event })"
      />
    </u-form-field>

    <!-- Board size -->
    <u-form-field
      :label="$t('admin.board_size')"
      :description="$t('admin.board_size_desc')"
      name="size"
      required
    >
      <u-select
        :model-value="modelValue.size"
        :items="sizeOptions"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, size: $event })"
      />
    </u-form-field>

    <!-- Board mode -->
    <u-form-field
      :label="$t('admin.board_mode')"
      :description="$t('admin.board_mode_desc')"
      name="mode"
    >
      <u-select
        :model-value="modelValue.mode"
        :items="modeOptions"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, mode: $event })"
      />
    </u-form-field>

    <!-- Date range -->
    <u-form-field
      :label="$t('admin.date_range')"
      :description="$t('admin.date_range_desc')"
      name="dateRange"
    >
      <u-input-date ref="inputDate" :model-value="dateRange" range locale="nl" class="w-full" @update:model-value="onDateRangeChange">
        <template #trailing>
          <u-popover :reference="inputDate?.inputsRef?.[0]?.$el">
            <u-button
              color="neutral"
              variant="link"
              size="sm"
              icon="i-lucide-calendar"
              :aria-label="$t('admin.date_range')"
              class="px-0"
            />
            <template #content>
              <u-calendar :model-value="dateRange" class="p-2" :number-of-months="2" range @update:model-value="onDateRangeChange" />
            </template>
          </u-popover>
        </template>
      </u-input-date>
    </u-form-field>

    <!-- Dice roll limit -->
    <u-form-field
      :label="$t('admin.dice_roll_limit')"
      :description="$t('admin.dice_roll_limit_desc')"
      name="diceRollLimit"
    >
      <div class="flex items-center gap-3">
        <u-input
          :model-value="modelValue.diceRollLimit"
          type="number"
          min="1"
          max="99"
          :disabled="modelValue.unlimitedRolls"
          class="w-32"
          @update:model-value="emit('update:modelValue', { ...modelValue, diceRollLimit: Number($event) })"
        />
        <u-checkbox
          :model-value="modelValue.unlimitedRolls"
          :label="$t('admin.dice_roll_unlimited')"
          @update:model-value="emit('update:modelValue', { ...modelValue, unlimitedRolls: $event })"
        />
      </div>
    </u-form-field>

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
import { today, getLocalTimeZone } from '@internationalized/date'
import type { CalendarDate } from '@internationalized/date'
import type { UserEntity } from '~/types/graphql'
import { fetchUsers } from '~/composables/useUsers'
import { useAllTeams, type TeamData } from '~/composables/useTeams'

export interface AssignedTeam {
  teamId: string
  team: { id: string; name: string; iconUrl?: string | null }
}

export interface AuthorOption {
  id: string
  discordUsername: string
  avatarUrl?: string | null
}

export interface BoardFormData {
  title: string
  description: string
  size: 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9'
  mode: 'SOLO' | 'TEAM'
  diceRollLimit: number
  unlimitedRolls: boolean
  selectedAuthors: AuthorOption[]
  assignedTeams: AssignedTeam[]
  startDate: CalendarDate | null
  endDate: CalendarDate | null
}

const props = defineProps<{
  modelValue: BoardFormData
  currentUserId?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData]
  'add-team': [team: { id: string; name: string; iconUrl: string | null }]
  'remove-team': [teamId: string]
}>()

const todayDate = today(getLocalTimeZone())

const sizeOptions = [
  { label: '5×5 (25 tiles)', value: 'SIZE_5X5' },
  { label: '7×7 (49 tiles)', value: 'SIZE_7X7' },
  { label: '9×9 (81 tiles)', value: 'SIZE_9X9' },
]

const modeOptions = [
  { label: 'Solo', value: 'SOLO' },
  { label: 'Team', value: 'TEAM' },
]

// Date range synced with modelValue
const dateRange = computed(() => ({
  start: props.modelValue.startDate,
  end: props.modelValue.endDate,
}))

function onDateRangeChange(val: { start: CalendarDate | null; end: CalendarDate | null } | null) {
  if (!val) return
  emit('update:modelValue', {
    ...props.modelValue,
    startDate: val.start ?? null,
    endDate: val.end ?? null,
  })
}

// ─── Author search ────────────────────────────────────────────────────────────

const inputDate = useTemplateRef('inputDate')
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
