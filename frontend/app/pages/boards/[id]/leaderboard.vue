<template>
  <nuxt-layout :title="$t('leaderboard.page_title')" :description="board?.title ?? ''">
    <template #links>
      <u-button
        variant="ghost"
        color="neutral"
        icon="i-lucide-arrow-left"
        :to="`/boards/${boardId}`"
        :label="$t('leaderboard.back_to_board')"
      />
    </template>

    <u-page-body>
      <u-container class="max-w-2xl">
        <div v-if="pending" class="flex flex-col gap-2">
          <u-skeleton v-for="i in 8" :key="i" class="h-12 rounded-lg" />
        </div>

        <u-alert
          v-else-if="error"
          color="error"
          icon="i-lucide-alert-circle"
          :title="$t('errors.generic')"
          class="my-4"
        />

        <div v-else-if="!entries.length" class="text-center py-16">
          <u-icon name="i-lucide-users" class="size-12 text-muted mx-auto mb-4" />
          <p class="text-lg font-medium osrs-font">{{ $t('leaderboard.no_players') }}</p>
        </div>

        <div v-else class="flex flex-col gap-1">
          <!-- Column headers -->
          <div class="flex items-center gap-2 px-2 pb-2 border-b border-default text-xs text-muted uppercase tracking-wide font-semibold">
            <span class="w-6 text-center shrink-0">#</span>
            <span class="flex-1">{{ $t('leaderboard.col_player') }}</span>
            <span class="w-16 text-right shrink-0">{{ $t('leaderboard.col_tile') }}</span>
            <span class="w-20 text-right shrink-0">{{ $t('leaderboard.col_remaining') }}</span>
          </div>

          <div
            v-for="entry in entries"
            :key="entry.playerId"
            class="flex items-center gap-2 px-2 py-2 rounded-lg transition-colors"
            :class="entry.rank === 1 ? 'bg-amber-500/10 ring-1 ring-amber-500/30' : 'hover:bg-muted/30'"
          >
            <span class="w-6 text-center text-sm shrink-0">
              <template v-if="entry.rank === 1">🥇</template>
              <template v-else-if="entry.rank === 2">🥈</template>
              <template v-else-if="entry.rank === 3">🥉</template>
              <template v-else><span class="text-xs text-muted font-bold">{{ entry.rank }}</span></template>
            </span>

            <u-avatar
              :src="entry.user.avatarUrl ?? undefined"
              :alt="entry.user.nickname ?? entry.user.discordUsername"
              size="sm"
              class="shrink-0"
            />

            <span class="flex-1 text-sm font-medium truncate">
              {{ entry.user.nickname ?? entry.user.discordUsername }}
            </span>

            <span class="w-16 text-right text-sm text-muted shrink-0">
              {{ $t('leaderboard.tile_number', { n: entry.currentPosition + 1 }) }}
            </span>

            <span
              class="w-20 text-right text-sm font-semibold shrink-0"
              :class="remainingClass(entry)"
              :title="remainingTitle(entry)"
            >
              {{ entry.tilesRemaining }}
              <span class="text-xs font-normal text-muted">{{ $t('leaderboard.tiles_left') }}</span>
            </span>
          </div>
        </div>

        <p v-if="entries.length" class="mt-6 text-xs text-muted text-center">
          {{ $t('leaderboard.player_count', { count: entries.length }) }}
        </p>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useBoard } from '~/composables/useBoards'
import { useLeaderboard, leaderboardRemainingClass, type LeaderboardEntry } from '~/composables/usePlayers'

const { t } = useI18n()
const route = useRoute()
const boardId = route.params.id as string

const [{ board }, { entries, pending, error }] = await Promise.all([
  useBoard(boardId),
  useLeaderboard(boardId),
])

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
