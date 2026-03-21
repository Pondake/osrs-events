<template>
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
        @roll="emit('roll')"
      />

      <!-- Roll result panel: shows snake/ladder jump info persistently -->
      <div v-if="lastRollResult" class="mt-3 pt-3 border-t border-default text-sm text-left">
        <p v-if="lastRollResult.jump === 'snake'" class="text-error font-semibold">
          {{
            $t('board.rolled_snake', {
              from: lastRollResult.landedOn + 1,
              to: lastRollResult.to + 1,
            })
          }}
        </p>

        <p v-else-if="lastRollResult.jump === 'ladder'" class="text-success font-semibold">
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

            <u-checkbox v-model="editUnlimited" :label="$t('admin.dice_roll_unlimited')" />
          </div>

          <u-button
            size="sm"
            color="primary"
            variant="soft"
            icon="i-lucide-check"
            class="mt-2 w-full"
            :loading="savingRollLimit"
            @click="emit('save-roll-limit')"
          >
            {{ $t('common.save') }}
          </u-button>
        </div>
      </template>
    </div>

    <!-- Current tile (player's position) -->
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

            <!-- Task description -->
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
            @click="emit('complete-tile', currentTile)"
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
            @click="emit('uncomplete-tile', currentTile)"
          >
            {{ $t('board.uncomplete_tile') }}
          </u-button>
        </div>
      </template>

      <p v-else class="text-sm text-muted">
        {{ $t('board.tile', { n: (myBoardState.currentPosition ?? 0) + 1 }) }}
      </p>
    </div>

    <!-- Clicked tile info (only when it differs from the player's current position) -->
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
          <p class="font-semibold text-sm">{{ clickedTileTitle }}</p>

          <p class="text-xs text-muted mt-0.5">
            {{ $t('board.tile', { n: clickedTile.position + 1 }) }}
          </p>

          <!-- Task description -->
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
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

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
}

interface PlayerState {
  id: string;
  userId: string;
  currentPosition: number;
  diceRollsToday: number;
  lastRollDate: string | null;
  completedTiles: Array<{ id: string; tileId: string }>;
}

interface RollResult {
  rolled: number;
  landedOn: number;
  to: number;
  jump: string | null;
}

const editRollLimit = defineModel<number>('editRollLimit', { required: true });

const editUnlimited = defineModel<boolean>('editUnlimited', { required: true });

const props = defineProps<{
  board: Board;
  myBoardState: PlayerState | null;
  currentTile: Tile | null;
  currentTileTitle: string;
  currentTileDescription: string | null;
  clickedTile: Tile | null;
  clickedTileTitle: string;
  clickedTileDescription: string | null;
  completedPositions: number[];
  rolling: boolean;
  lastRoll: number | null;
  lastRollResult: RollResult | null;
  completing: boolean;
  currentTileCompleted: boolean;
  editMode: boolean;
  savingRollLimit: boolean;
}>();

const emit = defineEmits<{
  roll: [];
  'complete-tile': [tile: Tile];
  'uncomplete-tile': [tile: Tile];
  'save-roll-limit': [];
}>();

const authStore = useAuthStore();

function isTileCompleted(position: number) {
  return props.completedPositions.includes(position);
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
