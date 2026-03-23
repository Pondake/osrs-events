<template>
  <div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <p class="text-sm font-semibold osrs-font uppercase tracking-wide text-muted">
        {{ $t('leaderboard.title') }}
      </p>

      <u-button
        v-if="boardId"
        variant="ghost"
        size="xs"
        color="neutral"
        trailing-icon="i-lucide-external-link"
        :to="`/boards/${boardId}/leaderboard`"
      />
    </div>

    <div v-if="loading" class="flex flex-col gap-2">
      <u-skeleton v-for="i in 3" :key="i" class="h-9 rounded-lg" />
    </div>

    <p v-else-if="!entries.length" class="text-xs text-muted text-center py-4">
      {{ $t('leaderboard.no_players') }}
    </p>

    <div v-else class="flex flex-col gap-1">
      <div
        v-for="entry in displayedEntries"
        :key="entry.playerId"
        class="flex items-center gap-2 px-2 py-1.5 rounded-lg"
        :class="isCurrentEntry(entry) ? 'bg-primary/10 ring-1 ring-primary/30' : 'hover:bg-muted/30'"
      >
        <span class="text-xs font-bold text-muted w-4 shrink-0 text-center">
          {{ entry.rank }}
        </span>

        <!-- Team token -->
        <template v-if="entry.team">
          <img
            v-if="entry.team.iconUrl"
            :src="entry.team.iconUrl"
            :alt="entry.team.name"
            class="size-6 object-contain shrink-0"
            style="image-rendering: pixelated"
          />
          <span
            v-else
            class="size-6 rounded shrink-0 bg-primary/20 flex items-center justify-center text-[8px] font-bold text-primary"
          >
            {{ entry.team.name.slice(0, 2).toUpperCase() }}
          </span>
        </template>
        <!-- Individual avatar -->
        <u-avatar
          v-else
          :src="entry.user.avatarUrl ?? undefined"
          :alt="entry.user.nickname ?? entry.user.discordUsername"
          size="xs"
          class="shrink-0"
        />

        <span class="flex-1 text-xs truncate font-medium">
          {{ entry.team?.name ?? entry.user.nickname ?? entry.user.discordUsername }}
        </span>

        <span class="text-xs text-muted shrink-0">
          #{{ entry.currentPosition + 1 }}
        </span>

        <span
          class="text-xs font-semibold w-12 text-right shrink-0"
          :class="remainingClass(entry)"
          :title="remainingTitle(entry)"
        >
          {{ entry.tilesRemaining }}🔲
        </span>
      </div>

      <div v-if="entries.length > PREVIEW_COUNT" class="mt-1 text-center">
        <u-button
          variant="ghost"
          size="xs"
          color="neutral"
          :to="`/boards/${boardId}/leaderboard`"
          :label="$t('leaderboard.show_all', { count: entries.length })"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  leaderboardRemainingClass,
  type LeaderboardEntry,
} from '~/composables/usePlayers'

const props = defineProps<{
  boardId: string
  currentPlayerId?: string
  currentTeamId?: string
}>()

const { t } = useI18n()

const PREVIEW_COUNT = 5

const LEADERBOARD_QUERY = `
  query BoardLeaderboard($boardId: ID!) {
    boardLeaderboard(boardId: $boardId) {
      entries {
        rank playerId currentPosition tilesRemaining pathHasLadder pathHasSnake
        user { id discordUsername nickname avatarUrl }
        team { id name iconUrl }
      }
    }
  }
`

const entries = ref<LeaderboardEntry[]>([])
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const result = await useGqlMutation<{
      boardLeaderboard: { entries: LeaderboardEntry[] } | null
    }>(LEADERBOARD_QUERY, { boardId: props.boardId })
    entries.value = result.boardLeaderboard?.entries ?? []
  } catch {
    entries.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
defineExpose({ refresh: load })

const displayedEntries = computed(() => entries.value.slice(0, PREVIEW_COUNT))

/** Highlight if this is the current player's or team's entry */
function isCurrentEntry(entry: LeaderboardEntry): boolean {
  if (props.currentTeamId && entry.team?.id === props.currentTeamId) return true
  return entry.playerId === props.currentPlayerId
}

function remainingClass(entry: LeaderboardEntry): string {
  return leaderboardRemainingClass(entry)
}

function remainingTitle(entry: LeaderboardEntry): string {
  if (entry.pathHasSnake && entry.pathHasLadder) return t('leaderboard.path_snake_and_ladder')
  if (entry.pathHasSnake) return t('leaderboard.path_has_snake')
  if (entry.pathHasLadder) return t('leaderboard.path_has_ladder')
  return t('leaderboard.tiles_remaining')
}
</script>
