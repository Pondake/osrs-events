<template>
  <nuxt-layout :title="board?.title || ''" :description="board?.description || ''" :pending="pending">
    <template #links>
      <div class="flex gap-2 shrink-0 flex-wrap">
        <!-- Show other players toggle -->
        <u-button
          v-if="otherPlayerStates.length > 0"
          :color="showOtherPlayers ? 'primary' : 'neutral'"
          :variant="showOtherPlayers ? 'subtle' : 'outline'"
          size="sm"
          icon="i-lucide-users"
          :label="$t('board.show_players')"
          @click="showOtherPlayers = !showOtherPlayers"
        />

        <!-- Edit mode toggle (admin / board editor) -->
        <u-button
          v-if="canEdit"
          :color="editMode ? 'primary' : 'neutral'"
          :variant="editMode ? 'solid' : 'outline'"
          size="sm"
          :icon="editMode ? 'i-lucide-eye' : 'i-lucide-pencil'"
          :label="editMode ? $t('board.view_mode') : $t('board.edit_mode')"
          @click="editMode = !editMode"
        />

        <!-- Board settings (admin / board editor) -->
        <u-button
          v-if="canEdit"
          color="neutral"
          variant="outline"
          size="sm"
          icon="i-lucide-settings"
          :aria-label="$t('admin.edit_board')"
          @click="showSettingsModal = true"
        />
      </div>
    </template>

    <!-- Loading skeleton: board grid + sidebar placeholder -->
    <template v-if="pending || (!board && !error)">
      <u-page-body>
        <u-container>
          <div class="flex flex-col lg:flex-row gap-8 items-start pb-8">
            <!-- Board skeleton: 7×7 default grid -->
            <div class="flex-1 w-full min-w-0 overflow-x-auto">
              <div class="board-parchment rounded-xl p-3 osrs-border">
                <div class="board-grid board-grid-7">
                  <u-skeleton
                    v-for="i in 49"
                    :key="i"
                    class="board-tile rounded-[6px]"
                  />
                </div>
              </div>
            </div>

            <!-- Sidebar skeleton -->
            <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">
              <u-skeleton class="h-48 rounded-xl" />
              <u-skeleton class="h-28 rounded-xl" />
              <u-skeleton class="h-24 rounded-xl" />
            </div>
          </div>
        </u-container>
      </u-page-body>
    </template>

    <!-- Error -->
    <u-container v-else-if="error && !board" class="my-8">
      <u-alert
        :title="$t('errors.generic')"
        :description="error.message"
        color="error"
        icon="i-lucide-alert-circle"
      />
    </u-container>

    <!-- Board content -->
    <template v-else-if="board">
      <!-- Access gate: user needs to join the board -->
      <template v-if="showAccessGate">
        <board-access-gate
          :access-mode="boardAccessMode"
          :required-guild-id="boardRequiredGuildId"
          :joining="joiningBoard"
          @join="doJoinBoard()"
          @join-with-code="onJoinWithCode"
        />
      </template>

      <u-page-body v-else>
        <u-container>
          <!-- TEAM mode: user has no team on this board -->
          <u-alert
            v-if="isTeamBoard && !playerBoardLoading && !playerBoard && authStore.user"
            color="warning"
            icon="i-lucide-users"
            class="mb-6"
            :title="$t('board.no_team_title')"
            :description="$t('board.no_team_desc')"
          >
            <template #actions>
              <u-button
                to="/teams"
                size="sm"
                color="neutral"
                variant="subtle"
                :label="$t('board.go_to_teams')"
              />
            </template>
          </u-alert>

          <Transition name="board-fade" appear>
          <div class="flex flex-col lg:flex-row gap-8 items-start pb-8">
            <!-- Game board -->
            <div class="flex-1 w-full min-w-0 overflow-x-auto">
              <div :class="boardMinWidth">
                <board-game-board
                  :tiles="board.tiles ?? []"
                  :board-size="board.size"
                  :current-position="playerBoard?.currentPosition ?? -1"
                  :completed-tile-positions="completedPositions"
                  :player-states="boardPlayerStates"
                  :edit-mode="editMode"
                  @tile-click="handleTileClick"
                />
              </div>
            </div>

            <!-- Sidebar (info + dice + leaderboard) -->
            <div class="w-full lg:w-64 shrink-0">
              <board-sidebar
                :board="board"
                :my-board-state="playerBoard"
                :clicked-tile="clickedTile"
                :completed-positions="completedPositions"
                v-model:edit-roll-limit="editRollLimit"
                v-model:edit-unlimited="editUnlimited"
                :rolling="rolling"
                :last-roll-result="lastRollResult"
                :completing="completing"
                :edit-mode="editMode"
                :saving-roll-limit="savingRollLimit"
                :leaderboard-key="leaderboardKey"
                @roll="onRoll"
                @complete-tile="onCompleteTile"
                @uncomplete-tile="onUncompleteTile"
                @save-roll-limit="saveRollLimit"
                @clear-tile="clearClickedTile"
              />
            </div>
          </div>
          </Transition>
        </u-container>
      </u-page-body>
    </template>

    <!-- Bingo modal -->
    <u-modal v-model:open="showBingo" :title="$t('board.bingo')">
      <template #body>
        <div class="text-center py-6">
          <p class="text-6xl mb-4">🎉</p>
          <p class="text-muted">{{ $t('board.bingo_desc') }}</p>
        </div>
      </template>
      <template #footer>
        <u-button block color="primary" :label="$t('common.close')" @click="showBingo = false" />
      </template>
    </u-modal>

    <!-- Tile editor modal -->
    <tile-edit-modal
      v-if="editMode && editingTile"
      :tile="(editingTile as any)"
      :total-tiles="totalTiles"
      :open="!!editingTile"
      @update:open="v => { if (!v) editingTile = null }"
      @saved="refresh"
      @deleted="refresh"
      @task-updated="onTaskUpdated"
    />

    <!-- Board settings modal -->
    <board-settings-modal
      v-if="board"
      :open="showSettingsModal"
      :board-id="boardId"
      :initial-data="boardSettingsData"
      @update:open="showSettingsModal = $event"
      @saved="onSettingsSaved"
    />
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useBoard } from '~/composables/useBoards'
import { useBoardPage } from '~/composables/useBoardPage'

const authStore = useAuthStore()
const route = useRoute()
const boardId = route.params.id as string

// ─── SSR board data ───────────────────────────────────────────────────────────

const { board, pending, error, refresh, updateBoard } = await useBoard(boardId)

// ─── All game logic via composable ───────────────────────────────────────────

const {
  totalTiles,
  boardMinWidth,
  isTeamBoard,
  boardAccess,
  joiningBoard,
  doJoinBoard,
  playerBoard,
  playerBoardLoading,
  otherPlayerStates,
  completedPositions,
  showOtherPlayers,
  editMode,
  boardPlayerStates,
  canEdit,
  clickedTile,
  editingTile,
  handleTileClick,
  onTaskUpdated,
  editRollLimit,
  editUnlimited,
  savingRollLimit,
  saveRollLimit,
  showSettingsModal,
  boardSettingsData,
  onSettingsSaved,
  rolling,
  completing,
  showBingo,
  lastRollResult,
  leaderboardKey,
  onRoll,
  onCompleteTile,
  onUncompleteTile,
} = useBoardPage(boardId, board, refresh, updateBoard as any)

const boardAccessMode = computed(() => (board.value as any)?.accessMode ?? 'OPEN')
const boardRequiredGuildId = computed(() => (board.value as any)?.requiredGuildId ?? null)

// Show AccessGate when: user is authenticated, board has loaded, but has no PlayerBoard
// and board access mode is not OPEN, OR board is OPEN but hasn't joined yet
const showAccessGate = computed(() =>
  !!board.value &&
  !!authStore.user &&
  !playerBoardLoading.value &&
  !playerBoard.value
)

async function onJoinWithCode(code: string) {
  await doJoinBoard(code)
}

function clearClickedTile() {
  clickedTile.value = null
}
</script>

<style scoped>
.board-fade-enter-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.board-fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
</style>
