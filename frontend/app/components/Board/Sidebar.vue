<template>
  <div class="w-full flex flex-col gap-4">
    <!-- Dice roller (only visible once current tile is completed) -->
    <transition name="sidebar-section">
    <div
      v-if="authStore.isAuthenticated && myBoardState !== null && currentTileCompleted"
      class="p-4 bg-muted/20 rounded-xl border border-default text-center"
    >
      <p class="text-sm font-semibold mb-3 osrs-font">{{ $t('board.roll_dice') }}</p>

      <dice-roller
        :rolling="rolling"
        :last-roll="lastRoll"
        :rolls-today="myBoardState?.diceRollsToday ?? 0"
        :roll-limit="board.diceRollLimit"
        :disabled="!currentTileCompleted"
        @roll="emit('roll')"
      />

      <!-- Roll result: shows snake/ladder jump info -->
      <div v-if="lastRollResult" class="mt-3 pt-3 border-t border-default text-sm text-left">
        <p v-if="lastRollResult.jump === 'snake'" class="text-error font-semibold">
          {{
            $t('board.rolled_snake', {
              from: (lastRollResult.landedOn ?? 0) + 1,
              to: lastRollResult.newPosition + 1,
            })
          }}
        </p>

        <p v-else-if="lastRollResult.jump === 'ladder'" class="text-success font-semibold">
          {{
            $t('board.rolled_ladder', {
              from: (lastRollResult.landedOn ?? 0) + 1,
              to: lastRollResult.newPosition + 1,
            })
          }}
        </p>

        <p v-else class="text-muted">
          {{ $t('board.you_rolled', { value: lastRollResult.rolled }) }}
        </p>
      </div>

    </div>
    </transition>

    <!-- Dice roll limit editor (admin edit mode) -->
    <transition name="sidebar-section">
      <div
        v-if="editMode && authStore.isAdmin"
        class="p-4 bg-muted/20 rounded-xl border border-default"
      >
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">
          {{ $t('admin.dice_roll_limit') }}
        </p>

        <div class="flex items-center gap-2 flex-wrap">
          <u-input
            v-model.number="editRollLimit"
            type="number"
            min="1"
            max="99"
            :disabled="editUnlimited"
            class="w-20"
            size="sm"
          />

          <u-checkbox v-model="editUnlimited" :label="$t('admin.dice_roll_unlimited')" />
        </div>

        <u-button
          size="sm"
          color="primary"
          variant="soft"
          icon="i-lucide-check"
          class="mt-2 w-full"
          :loading="savingRollLimit"
          :label="$t('common.save')"
          @click="emit('save-roll-limit')"
        />
      </div>
    </transition>

    <!-- Current tile (player's position) -->
    <transition name="sidebar-section">
    <div v-if="myBoardState !== null" class="p-4 bg-muted/20 rounded-xl border border-default">
      <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
        {{ $t('board.your_task') }}
      </p>

      <template v-if="currentTile">
        <div class="flex items-start gap-3">
          <img
            v-if="currentTile.task?.iconUrl"
            :src="currentTile.task.iconUrl"
            :alt="currentTileTitle"
            class="h-10 w-10 object-contain flex-shrink-0"
          />

          <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">{{ currentTileTitle }}</p>

            <p class="text-xs text-muted mt-0.5">
              {{ $t('board.tile', { n: currentTile.position + 1 }) }}
            </p>

            <p v-if="currentTileDescription" class="text-xs text-muted mt-1 leading-relaxed">
              {{ currentTileDescription }}
            </p>

            <u-badge
              v-if="currentTile.type !== 'NORMAL'"
              :color="currentTile.type === 'SNAKE' ? 'error' : 'warning'"
              size="sm"
              class="mt-1"
            >
              {{ currentTile.type === 'SNAKE' ? '🐍' : '🪜' }}
              → {{ $t('board.tile', { n: (currentTile.targetPosition ?? 0) + 1 }) }}
            </u-badge>
          </div>
        </div>

        <!-- Complete / uncomplete the current tile -->
        <div class="mt-3 flex gap-2">
          <u-button
            v-if="!isTileCompleted(currentTile.position)"
            color="success"
            variant="solid"
            size="sm"
            icon="i-lucide-check"
            class="flex-1"
            :loading="completing"
            :label="$t('board.complete_tile')"
            @click="emit('complete-tile', currentTile)"
          />

          <u-button
            v-else
            color="neutral"
            variant="outline"
            size="sm"
            icon="i-lucide-x"
            class="flex-1"
            :loading="completing"
            :label="$t('board.uncomplete_tile')"
            @click="emit('uncomplete-tile', currentTile)"
          />
        </div>
      </template>

      <p v-else class="text-sm text-muted">
        {{ $t('board.tile', { n: (myBoardState.currentPosition ?? 0) + 1 }) }}
      </p>
    </div>
    </transition>

    <!-- Clicked tile info (when different from player's current position) -->
    <transition name="sidebar-section">
    <div
      v-if="clickedTile && clickedTile.position !== (myBoardState?.currentPosition ?? -1)"
      class="p-4 bg-muted/20 rounded-xl border border-default"
    >
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-semibold text-muted uppercase tracking-wide">
          {{ $t('board.tile_info') }}
        </p>
        <u-button
          size="xs"
          variant="ghost"
          color="neutral"
          icon="i-lucide-x"
          :aria-label="$t('common.close')"
          @click="emit('clear-tile')"
        />
      </div>

      <div class="flex items-start gap-3">
        <img
          v-if="clickedTile.task?.iconUrl"
          :src="clickedTile.task.iconUrl"
          :alt="clickedTileTitle"
          class="h-10 w-10 object-contain flex-shrink-0"
        />

        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm">{{ clickedTileTitle }}</p>

          <p class="text-xs text-muted mt-0.5">
            {{ $t('board.tile', { n: clickedTile.position + 1 }) }}
          </p>

          <p v-if="clickedTileDescription" class="text-xs text-muted mt-1 leading-relaxed">
            {{ clickedTileDescription }}
          </p>

          <u-badge
            v-if="clickedTile.type !== 'NORMAL'"
            :color="clickedTile.type === 'SNAKE' ? 'error' : 'warning'"
            size="sm"
            class="mt-1"
          >
            {{ clickedTile.type === 'SNAKE' ? '🐍 Snake' : '🪜 Ladder' }}
            → {{ $t('board.tile', { n: (clickedTile.targetPosition ?? 0) + 1 }) }}
          </u-badge>
        </div>
      </div>
    </div>
    </transition>

    <!-- Editors -->
    <div class="p-4 bg-muted/20 rounded-xl border border-default">
      <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
        {{ $t('admin.editors') }}
      </p>

      <div class="flex flex-wrap gap-2">
        <div v-for="author in board.authors" :key="author.id" class="flex items-center gap-1.5">
          <u-avatar
            :src="author.user.avatarUrl ?? undefined"
            :alt="author.user.nickname || author.user.discordUsername"
            size="xs"
          />

          <span class="text-xs">{{ author.user.nickname || author.user.discordUsername }}</span>
        </div>
      </div>
    </div>

    <!-- Meta -->
    <div class="p-4 bg-muted/20 rounded-xl border border-default">
      <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
        {{ $t('boards.meta') }}
      </p>

      <div class="flex flex-wrap gap-2 mt-1">
        <u-badge color="neutral" variant="subtle" icon="i-lucide-calendar">
          {{ formatDate(board.startDate) }} – {{ formatDate(board.endDate) }}
        </u-badge>

        <u-badge color="neutral" variant="subtle" icon="i-lucide-grid-3x3">
          {{ formatBoardSize(board.size) }}
        </u-badge>

        <u-badge
          v-if="board.diceRollLimit"
          color="neutral"
          variant="subtle"
          icon="i-lucide-dice-6"
        >
          {{ $t('boards.roll_limit', { limit: board.diceRollLimit }) }}
        </u-badge>

        <u-badge v-else color="neutral" variant="subtle" icon="i-lucide-dice-6">
          {{ $t('dice.unlimited') }}
        </u-badge>

        <u-badge
          :color="(board as any).mode === 'TEAM' ? 'warning' : 'neutral'"
          variant="subtle"
          icon="i-lucide-users-round"
        >
          {{ (board as any).mode === 'TEAM' ? $t('board.mode_team') : $t('board.mode_solo') }}
        </u-badge>
      </div>
    </div>

    <!-- Leaderboard — key forces remount on roll/complete/uncomplete -->
    <div class="p-4 bg-muted/20 rounded-xl border border-default">
      <board-leaderboard
        :key="leaderboardKey"
        :board-id="board.id"
        :current-player-id="myBoardState?.id"
        :current-team-id="(myBoardState as any)?.teamId ?? undefined"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TileEntity, BoardEntity, PlayerBoardEntity, RollResultEntity } from '~/types/graphql'
import { useAuthStore } from '~/stores/auth'
import { formatBoardSize, formatDate } from '~/utils/board'

const editRollLimit = defineModel<number>('editRollLimit', { required: true })
const editUnlimited = defineModel<boolean>('editUnlimited', { required: true })

const props = defineProps<{
  board: BoardEntity
  myBoardState: PlayerBoardEntity | null
  clickedTile: TileEntity | null
  completedPositions: number[]
  rolling: boolean
  lastRollResult: RollResultEntity | null
  completing: boolean
  editMode: boolean
  savingRollLimit: boolean
  leaderboardKey: number
}>()

const emit = defineEmits<{
  roll: []
  'complete-tile': [tile: TileEntity]
  'uncomplete-tile': [tile: TileEntity]
  'save-roll-limit': []
  'clear-tile': []
}>()

const authStore = useAuthStore()
const { t } = useI18n()

// ─── Computed tile state (derived from board + myBoardState) ──────────────────

const currentTile = computed((): TileEntity | null => {
  if (!props.myBoardState) return null
  return (
    (props.board.tiles ?? []).find(t => t.position === props.myBoardState!.currentPosition) ?? null
  )
})

const currentTileTitle = computed(
  () =>
    (currentTile.value as any)?.displayTitle ??
    currentTile.value?.titleOverride ??
    currentTile.value?.task?.title ??
    t('tile_editor.no_task'),
)

const currentTileDescription = computed(() => currentTile.value?.task?.description ?? null)

const clickedTileTitle = computed(
  () =>
    (props.clickedTile as any)?.displayTitle ??
    props.clickedTile?.titleOverride ??
    props.clickedTile?.task?.title ??
    t('tile_editor.no_task'),
)

const clickedTileDescription = computed(() => props.clickedTile?.task?.description ?? null)

const currentTileCompleted = computed(() => {
  if (!props.myBoardState) return false
  return props.completedPositions.includes(props.myBoardState.currentPosition)
})

/** The numeric rolled value to pass to DiceRoller */
const lastRoll = computed(() => props.lastRollResult?.rolled ?? null)

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isTileCompleted(position: number): boolean {
  return props.completedPositions.includes(position)
}
</script>

<style scoped>
.sidebar-section-enter-active,
.sidebar-section-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sidebar-section-enter-from,
.sidebar-section-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
