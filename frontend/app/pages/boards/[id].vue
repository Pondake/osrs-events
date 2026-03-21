<template>
  <nuxt-layout :title="board?.title || ''" :description="board?.description || ''">
    <template #links>
      <div class="flex gap-2 shrink-0 flex-wrap">
        <!-- Show other players (only when others exist — loaded after mount) -->
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

            <!-- Sidebar component -->
            <board-sidebar
              :board="board"
              :my-board-state="myBoardState"
              :current-tile="currentTile"
              :current-tile-title="currentTileTitle"
              :current-tile-description="currentTileDescription"
              :clicked-tile="clickedTile"
              v-model:edit-roll-limit="editRollLimit"
              :clicked-tile-title="clickedTileTitle"
              v-model:edit-unlimited="editUnlimited"
              :clicked-tile-description="clickedTileDescription"
              :completed-positions="completedPositions"
              :rolling="rolling"
              :last-roll="lastRoll"
              :last-roll-result="lastRollResult"
              :completing="completing"
              :current-tile-completed="currentTileCompleted"
              :edit-mode="editMode"
              :saving-roll-limit="savingRollLimit"
              @roll="rollDice"
              @complete-tile="completeTile"
              @uncomplete-tile="uncompleteTile"
              @save-roll-limit="saveRollLimit"
            />
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
  description: string | null;
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
  user: { id: string; discordUsername: string; nickname: string | null; avatarUrl: string | null };
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
  user?: { id: string; discordUsername: string; nickname: string | null; avatarUrl: string | null };
}

const BOARD_QUERY = `query Board($id: ID!) {
  board(id: $id) {
    id title description startDate endDate size diceRollLimit
    authors { id user { id discordUsername nickname avatarUrl } }
    tiles { id position type targetPosition titleOverride displayTitle task { id title iconUrl description } }
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
    user { id discordUsername nickname avatarUrl }
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

const currentTileDescription = computed(() => currentTile.value?.task?.description ?? null);

// Clicked tile — shown in sidebar for info only (not completable unless current)
const clickedTile = ref<Tile | null>(null);
const clickedTileTitle = computed(
  () =>
    clickedTile.value?.displayTitle ??
    clickedTile.value?.titleOverride ??
    clickedTile.value?.task?.title ??
    t('tile_editor.no_task'),
);
const clickedTileDescription = computed(() => clickedTile.value?.task?.description ?? null);

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
</script>
