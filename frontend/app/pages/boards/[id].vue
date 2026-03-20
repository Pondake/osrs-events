<template>
  <nuxt-layout :title="board?.title || ''" :description="board?.description || ''">
         <template #links>
              <div class="flex gap-2 shrink-0 flex-wrap">
                <!-- Show other players (only when others exist) -->
                <u-button
                  v-if="otherPlayerStates.length > 0"
                  :color="showOtherPlayers ? 'primary' : 'neutral'"
                  :variant="showOtherPlayers ? 'subtle' : 'outline'"
                  size="sm"
                  icon="i-lucide-users"
                  @click="showOtherPlayers = !showOtherPlayers"
                >
                  {{ $t('board.show_players') }}
                </u-button>

                <!-- Admin edit toggle -->
                <u-button
                  v-if="authStore.isAdmin"
                  :color="editMode ? 'primary' : 'neutral'"
                  :variant="editMode ? 'solid' : 'outline'"
                  size="sm"
                  :icon="editMode ? 'i-lucide-eye' : 'i-lucide-pencil'"
                  @click="editMode = !editMode"
                >
                  {{ editMode ? $t('board.view_mode') : $t('board.edit_mode') }}
                </u-button>
              </div>
            </template>

    <u-skeleton v-if="pending" class="h-96 rounded-xl mx-auto max-w-4xl mt-8" />

    <u-container v-else-if="error" class="my-8">
      <u-alert
        :title="$t('errors.generic')"
        :description="error.message"
        color="error"
        icon="i-lucide-alert-circle"
      />
    </u-container>

    <template v-else-if="board">
      <u-page-body>
        <u-container>
          <!-- Board + Sidebar -->
          <div class="flex flex-col lg:flex-row gap-8 items-start pb-8">
            <!-- Game Board -->
            <div class="flex-1 w-full min-w-0 overflow-x-auto">
              <div :class="boardMinWidth">
                <board-game-board
                  :tiles="board.tiles"
                  :board-size="board.size"
                  :current-position="myBoardState?.currentPosition ?? -1"
                  :completed-tile-positions="completedPositions"
                  :player-states="boardPlayerStates"
                  :edit-mode="editMode"
                  @tile-click="handleTileClick"
                />
              </div>
            </div>

            <!-- Sidebar -->
            <div class="w-full lg:w-64 flex flex-col gap-4">
              <!-- Dice roller -->
              <div
                v-if="authStore.isAuthenticated"
                class="p-4 bg-muted/20 rounded-xl border border-default text-center"
              >
                <p class="text-sm font-semibold mb-3 osrs-font">{{ $t('board.roll_dice') }}</p>

                <!-- Must complete current tile before rolling -->
                <u-alert
                  v-if="myBoardState !== null && !currentTileCompleted"
                  :title="$t('dice.complete_tile_first')"
                  color="warning"
                  variant="subtle"
                  icon="i-lucide-lock"
                  class="mb-3 text-left text-sm"
                />

                <dice-roller
                  :rolling="rolling"
                  :last-roll="lastRoll"
                  :rolls-today="myBoardState?.diceRollsToday ?? 0"
                  :roll-limit="board.diceRollLimit"
                  :disabled="!currentTileCompleted"
                  @roll="rollDice"
                />

                <!-- Roll result panel: shows snake/ladder jump info persistently -->
                <div
                  v-if="lastRollResult"
                  class="mt-3 pt-3 border-t border-default text-sm text-left"
                >
                  <p v-if="lastRollResult.jump === 'snake'" class="text-error font-semibold">
                    {{
                      $t('board.rolled_snake', {
                        from: lastRollResult.landedOn + 1,
                        to: lastRollResult.to + 1,
                      })
                    }}
                  </p>

                  <p
                    v-else-if="lastRollResult.jump === 'ladder'"
                    class="text-success font-semibold"
                  >
                    {{
                      $t('board.rolled_ladder', {
                        from: lastRollResult.landedOn + 1,
                        to: lastRollResult.to + 1,
                      })
                    }}
                  </p>

                  <p v-else class="text-muted">
                    {{ $t('board.you_rolled', { value: lastRollResult.rolled }) }}
                  </p>
                </div>

                <!-- Edit mode: dice roll limit editor -->
                <template v-if="editMode && authStore.isAdmin">
                  <u-separator class="my-4" />

                  <div class="text-left">
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

                      <u-checkbox
                        v-model="editUnlimited"
                        :label="$t('admin.dice_roll_unlimited')"
                      />
                    </div>

                    <u-button
                      size="sm"
                      color="primary"
                      variant="soft"
                      icon="i-lucide-check"
                      class="mt-2 w-full"
                      :loading="savingRollLimit"
                      @click="saveRollLimit"
                    >
                      {{ $t('common.save') }}
                    </u-button>
                  </div>
                </template>
              </div>

              <!-- Current tile (player's position) -->
              <div
                v-if="myBoardState !== null"
                class="p-4 bg-muted/20 rounded-xl border border-default"
              >
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
                      <p class="font-semibold text-sm truncate">{{ currentTileTitle }}</p>

                      <p class="text-xs text-muted mt-0.5">
                        {{ $t('board.tile', { n: currentTile.position + 1 }) }}
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

                  <!-- Complete / uncomplete only the current tile -->
                  <div class="mt-3 flex gap-2">
                    <u-button
                      v-if="!isTileCompleted(currentTile.position)"
                      color="success"
                      variant="solid"
                      size="sm"
                      icon="i-lucide-check"
                      class="flex-1"
                      :loading="completing"
                      @click="completeTile(currentTile)"
                    >
                      {{ $t('board.complete_tile') }}
                    </u-button>

                    <u-button
                      v-else
                      color="neutral"
                      variant="outline"
                      size="sm"
                      icon="i-lucide-x"
                      class="flex-1"
                      :loading="completing"
                      @click="uncompleteTile(currentTile)"
                    >
                      {{ $t('board.uncomplete_tile') }}
                    </u-button>
                  </div>
                </template>

                <p v-else class="text-sm text-muted">
                  {{ $t('board.tile', { n: (myBoardState.currentPosition ?? 0) + 1 }) }}
                </p>
              </div>

              <!-- Clicked tile info (only shown when it differs from current position) -->
              <div
                v-if="clickedTile && clickedTile.position !== (myBoardState?.currentPosition ?? -1)"
                class="p-4 bg-muted/20 rounded-xl border border-default"
              >
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
                  {{ $t('board.tile_info') }}
                </p>

                <div class="flex items-start gap-3">
                  <img
                    v-if="clickedTile.task?.iconUrl"
                    :src="clickedTile.task.iconUrl"
                    :alt="clickedTileTitle"
                    class="h-10 w-10 object-contain flex-shrink-0"
                  />

                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate">{{ clickedTileTitle }}</p>

                    <p class="text-xs text-muted mt-0.5">
                      {{ $t('board.tile', { n: clickedTile.position + 1 }) }}
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

              <!-- Editors -->
              <div class="p-4 bg-muted/20 rounded-xl border border-default">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
                  {{ $t('admin.editors') }}
                </p>

                <div class="flex flex-wrap gap-2">
                  <div
                    v-for="author in board.authors"
                    :key="author.id"
                    class="flex items-center gap-1.5"
                  >
                    <u-avatar
                      :src="author.user.avatarUrl ?? undefined"
                      :alt="author.user.discordUsername"
                      size="xs"
                    />

                    <span class="text-xs">{{ author.user.discordUsername }}</span>
                  </div>
                </div>
              </div>

              <!-- Other meta -->
              <div class="p-4 bg-muted/20 rounded-xl border border-default">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">
                  {{ $t('boards.meta') }}
                </p>

                <div class="flex flex-wrap gap-2">
                  <div class="flex flex-wrap gap-2 mt-3">
                    <u-badge color="neutral" variant="subtle" icon="i-lucide-calendar">
                      {{ formatDate(board.startDate) }} – {{ formatDate(board.endDate) }}
                    </u-badge>

                    <u-badge color="neutral" variant="subtle" icon="i-lucide-grid-3x3">
                      {{ boardSizeLabel(board.size) }}
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
                  </div>
                </div>
              </div>
            </div>
          </div>
        </u-container>
      </u-page-body>
    </template>

    <!-- Bingo modal: shown when the last tile on the board is completed -->
    <u-modal v-model:open="showBingo" :title="$t('board.bingo')">
      <template #body>
        <div class="text-center py-6">
          <p class="text-6xl mb-4">🎉</p>

          <p class="text-muted">{{ $t('board.bingo_desc') }}</p>
        </div>
      </template>

      <template #footer>
        <u-button block color="primary" @click="showBingo = false">
          {{ $t('common.close') }}
        </u-button>
      </template>
    </u-modal>

    <!-- Tile editor modal (admin edit mode) -->
    <tile-edit-modal
      v-if="editMode && editingTile"
      :tile="editingTile"
      :total-tiles="totalTiles"
      :open="!!editingTile"
      @update:open="
        v => {
          if (!v) editingTile = null;
        }
      "
      @saved="onTileSaved"
      @deleted="onTileSaved"
    />
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();

const boardId = route.params.id as string;

interface Task {
  id: string;
  title: string;
  iconUrl: string | null;
}
interface Tile {
  id: string;
  position: number;
  type: 'NORMAL' | 'SNAKE' | 'LADDER';
  targetPosition: number | null;
  titleOverride: string | null;
  displayTitle?: string | null;
  task: Task | null;
}
interface BoardAuthor {
  id: string;
  user: { id: string; discordUsername: string; avatarUrl: string | null };
}
interface Board {
  id: string;
  title: string;
  description: string | null;
  startDate: string | null;
  endDate: string | null;
  size: 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9';
  diceRollLimit: number | null;
  authors: BoardAuthor[];
  tiles: Tile[];
}
interface PlayerState {
  id: string;
  userId: string;
  currentPosition: number;
  diceRollsToday: number;
  lastRollDate: string | null;
  completedTiles: Array<{ id: string; tileId: string }>;
  user?: { id: string; discordUsername: string; avatarUrl: string | null };
}

const BOARD_QUERY = `query Board($id: ID!) {
  board(id: $id) {
    id title description startDate endDate size diceRollLimit
    authors { id user { id discordUsername avatarUrl } }
    tiles { id position type targetPosition titleOverride displayTitle task { id title iconUrl } }
  }
}`;

const MY_STATE_QUERY = `query MyBoardState($boardId: ID!) {
  myBoardState(boardId: $boardId) {
    id userId boardId currentPosition diceRollsToday lastRollDate
    completedTiles { id tileId }
  }
}`;

const PLAYERS_QUERY = `query BoardPlayerStates($boardId: ID!) {
  boardPlayerStates(boardId: $boardId) {
    id userId currentPosition
    user { id discordUsername avatarUrl }
  }
}`;

const UPDATE_BOARD_MUTATION = `mutation UpdateBoard($id: ID!, $input: UpdateBoardInput!) {
  updateBoard(id: $id, input: $input) { id diceRollLimit }
}`;

const { data, pending, error, refresh } = await useGql<{ board: Board }>(BOARD_QUERY, {
  id: boardId,
});
const board = computed(() => data.value?.board ?? null);

const totalTiles = computed(() => {
  const map = { SIZE_5X5: 25, SIZE_7X7: 49, SIZE_9X9: 81 };
  return map[board.value?.size ?? 'SIZE_7X7'] ?? 49;
});

// Minimum board width so it stays readable on small screens with horizontal scroll
const boardMinWidth = computed(() => {
  const map: Record<string, string> = {
    SIZE_5X5: 'min-w-[300px]',
    SIZE_7X7: 'min-w-[380px]',
    SIZE_9X9: 'min-w-[460px]',
  };
  return map[board.value?.size ?? 'SIZE_7X7'] ?? 'min-w-[380px]';
});

// Player states
const myBoardState = ref<PlayerState | null>(null);
const otherPlayerStates = ref<PlayerState[]>([]);

async function loadPlayerState() {
  if (!authStore.isAuthenticated) return;
  try {
    const result = await useGqlMutation<{ myBoardState: PlayerState }>(MY_STATE_QUERY, { boardId });
    myBoardState.value = result.myBoardState;
  } catch {
    /* not authenticated */
  }
  try {
    const result = await useGqlMutation<{ boardPlayerStates: PlayerState[] }>(PLAYERS_QUERY, {
      boardId,
    });
    otherPlayerStates.value = (result.boardPlayerStates ?? []).filter(
      p => p.userId !== authStore.user?.id,
    );
  } catch {
    /**/
  }
}

onMounted(loadPlayerState);

const completedPositions = computed(() => {
  if (!myBoardState.value || !board.value) return [];
  const tileIdToPosition = new Map(board.value.tiles.map(t => [t.id, t.position]));
  return myBoardState.value.completedTiles
    .map(ct => tileIdToPosition.get(ct.tileId) ?? -1)
    .filter(p => p >= 0);
});

function isTileCompleted(position: number) {
  return completedPositions.value.includes(position);
}

// Current tile derived from player position
const currentTile = computed((): Tile | null => {
  if (!myBoardState.value || !board.value) return null;
  return board.value.tiles.find(t => t.position === myBoardState.value!.currentPosition) ?? null;
});

const currentTileTitle = computed(
  () =>
    currentTile.value?.displayTitle ??
    currentTile.value?.titleOverride ??
    currentTile.value?.task?.title ??
    t('tile_editor.no_task'),
);

// Clicked tile — shown in sidebar for info only (not completable unless current)
const clickedTile = ref<Tile | null>(null);
const clickedTileTitle = computed(
  () =>
    clickedTile.value?.displayTitle ??
    clickedTile.value?.titleOverride ??
    clickedTile.value?.task?.title ??
    t('tile_editor.no_task'),
);

// Edit mode
const editMode = ref(false);
const editingTile = ref<null | {
  id?: string;
  position: number;
  boardId: string;
  task: Task | null;
  titleOverride: string | null;
  type: 'NORMAL' | 'SNAKE' | 'LADDER';
  targetPosition: number | null;
}>(null);

const showOtherPlayers = ref(false);

// Own player shown on board at their current tile
const myPlayerForBoard = computed(() => {
  if (!myBoardState.value || !authStore.user) return null;
  return {
    userId: authStore.user.id,
    currentPosition: myBoardState.value.currentPosition,
    user: {
      id: authStore.user.id,
      discordUsername: authStore.user.discordUsername,
      avatarUrl: authStore.user.avatarUrl ?? null,
    },
  };
});

// Players array: own avatar always shown + others when toggled
const boardPlayerStates = computed(() => [
  ...(myPlayerForBoard.value ? [myPlayerForBoard.value] : []),
  ...(showOtherPlayers.value ? otherPlayerStates.value : []),
]);

// Must complete current tile before rolling
const currentTileCompleted = computed(() => {
  if (!myBoardState.value) return false;
  return isTileCompleted(myBoardState.value.currentPosition);
});

// Roll limit edit state (admin)
const editRollLimit = ref(3);
const editUnlimited = ref(false);
const savingRollLimit = ref(false);

watch(
  board,
  b => {
    if (b) {
      editRollLimit.value = b.diceRollLimit ?? 3;
      editUnlimited.value = b.diceRollLimit === null;
    }
  },
  { immediate: true },
);

function handleTileClick(position: number) {
  const tile = board.value?.tiles.find(t => t.position === position) ?? null;

  if (editMode.value && authStore.isAdmin) {
    // Admin edit mode: open tile editor
    editingTile.value = {
      id: tile?.id,
      position,
      boardId,
      task: tile?.task ?? null,
      titleOverride: tile?.titleOverride ?? null,
      type: tile?.type ?? 'NORMAL',
      targetPosition: tile?.targetPosition ?? null,
    };
  } else {
    // Player mode: show clicked tile info in sidebar
    clickedTile.value = tile;
  }
}

async function onTileSaved() {
  await refresh();
}

// Dice
const rolling = ref(false);
const lastRoll = ref<number | null>(null);
const completing = ref(false);

// Persistent roll result panel (snake/ladder jump info)
interface RollResult {
  rolled: number;
  landedOn: number;
  to: number;
  jump: string | null;
}
const lastRollResult = ref<RollResult | null>(null);

// Bingo modal: shown when all tiles are completed
const showBingo = ref(false);

const ROLL_MUTATION = `mutation RollDice($boardId: ID!) {
  rollDice(boardId: $boardId) {
    rolled previousPosition newPosition landedOn jump
    playerBoard { id currentPosition diceRollsToday lastRollDate completedTiles { id tileId } }
  }
}`;

async function rollDice() {
  if (rolling.value) return;
  rolling.value = true;
  try {
    const result = await useGqlMutation<{
      rollDice: {
        rolled: number;
        landedOn: number;
        newPosition: number;
        jump: string | null;
        playerBoard: PlayerState;
      };
    }>(ROLL_MUTATION, { boardId });

    const roll = result.rollDice;
    lastRoll.value = roll.rolled;
    myBoardState.value = roll.playerBoard;
    clickedTile.value = null; // clear info panel after rolling

    // Store for persistent result panel
    lastRollResult.value = {
      rolled: roll.rolled,
      landedOn: roll.landedOn,
      to: roll.newPosition,
      jump: roll.jump,
    };

    if (roll.jump === 'snake') {
      toast.add({
        title: t('board.rolled_snake', { from: roll.landedOn + 1, to: roll.newPosition + 1 }),
        color: 'error',
      });
    } else if (roll.jump === 'ladder') {
      toast.add({
        title: t('board.rolled_ladder', { from: roll.landedOn + 1, to: roll.newPosition + 1 }),
        color: 'success',
      });
    } else {
      toast.add({ title: t('board.you_rolled', { value: roll.rolled }), color: 'primary' });
    }
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    rolling.value = false;
  }
}

const COMPLETE_TILE = `mutation CompleteTile($boardId: ID!, $tileId: ID!) {
  completeTile(boardId: $boardId, tileId: $tileId) { id completedTiles { id tileId } }
}`;

const UNCOMPLETE_TILE = `mutation UncompleteTile($boardId: ID!, $tileId: ID!) {
  uncompleteTile(boardId: $boardId, tileId: $tileId) { id completedTiles { id tileId } }
}`;

async function completeTile(tile: Tile) {
  completing.value = true;
  try {
    const result = await useGqlMutation<{ completeTile: PlayerState }>(COMPLETE_TILE, {
      boardId,
      tileId: tile.id,
    });
    if (myBoardState.value) {
      myBoardState.value.completedTiles = result.completeTile.completedTiles;
    }
    toast.add({ title: t('board.tile_completed'), color: 'success' });

    // Show bingo modal when the last tile (end of board) is completed
    if (tile.position === totalTiles.value - 1) {
      showBingo.value = true;
    }
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    completing.value = false;
  }
}

async function uncompleteTile(tile: Tile) {
  completing.value = true;
  try {
    const result = await useGqlMutation<{ uncompleteTile: PlayerState }>(UNCOMPLETE_TILE, {
      boardId,
      tileId: tile.id,
    });
    if (myBoardState.value && result.uncompleteTile) {
      myBoardState.value.completedTiles = result.uncompleteTile.completedTiles;
    }
    toast.add({ title: t('board.tile_uncompleted'), color: 'neutral' });
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    completing.value = false;
  }
}

async function saveRollLimit() {
  savingRollLimit.value = true;
  try {
    const newLimit = editUnlimited.value ? null : editRollLimit.value;
    await useGqlMutation(UPDATE_BOARD_MUTATION, {
      id: boardId,
      input: { diceRollLimit: newLimit },
    });
    await refresh();
    toast.add({ title: t('admin.board_updated'), color: 'success' });
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    savingRollLimit.value = false;
  }
}

function boardSizeLabel(size: string) {
  const map: Record<string, string> = { SIZE_5X5: '5×5', SIZE_7X7: '7×7', SIZE_9X9: '9×9' };
  return map[size] ?? size;
}

function formatDate(date: string | null | undefined) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
</script>
