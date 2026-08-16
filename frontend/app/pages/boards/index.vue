<template>
  <nuxt-layout :title="$t('boards.title')" :description="$t('boards.subtitle')">
    <template v-if="authStore.isAdmin || authStore.isEditor" #links>
      <u-button
        color="primary"
        icon="i-lucide-plus"
        :label="$t('admin.create_board')"
        @click="showCreateModal = true"
      />
    </template>

    <u-page-body>
      <u-container>
        <!-- Loading -->
        <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <u-skeleton v-for="i in 6" :key="i" class="h-48 rounded-xl" />
        </div>

        <!-- Error -->
        <u-alert
          v-else-if="error"
          :title="$t('boards.load_error')"
          :description="error.message"
          color="error"
          icon="i-lucide-alert-circle"
        />

        <!-- Empty -->
        <div v-else-if="!boards.length" class="text-center py-16">
          <u-icon name="i-lucide-layout-grid" class="size-12 text-muted mx-auto mb-4" />

          <p class="text-lg font-medium">{{ $t('boards.no_boards') }}</p>

          <p class="text-sm text-muted mt-1">{{ $t('boards.no_boards_desc') }}</p>
        </div>

        <!-- Board cards -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <nuxt-link
            v-for="{ board, status, access } in decoratedBoards"
            :key="board.id"
            :to="`/boards/${board.id}`"
          >
            <u-page-card
              class="h-full hover:border-primary transition-colors cursor-pointer"
              :ui="{ body: 'w-full' }"
            >
              <!-- PageCard's wrapper is items-start, so body needs w-full above
                   for the status to reach the right edge of the card. -->
              <template #title>
                <div class="flex items-center justify-between gap-3 w-full">
                  <span class="truncate">{{ board.title }}</span>

                  <div
                    class="flex items-center gap-1.5 text-xs font-medium rounded-md px-2 py-1 shrink-0"
                    :class="status.class"
                  >
                    <u-icon :name="status.icon" class="size-3.5" />

                    <span>{{ $t(status.key) }}</span>
                  </div>
                </div>
              </template>

              <template #description>
                <div class="flex flex-col gap-2 mt-2">
                  <div class="flex items-center gap-2 text-sm text-muted">
                    <u-icon name="i-lucide-calendar" class="size-4" />

                    <span>{{ formatDate(board.startDate) }} – {{ formatDate(board.endDate) }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-sm text-muted">
                    <u-icon name="i-lucide-grid-3x3" class="size-4" />

                    <span>{{ $t('boards.size', { size: formatBoardSize(board.size) }) }}</span>
                  </div>

                  <div
                    v-if="board.diceRollLimit"
                    class="flex items-center gap-2 text-sm text-muted"
                  >
                    <u-icon name="i-lucide-dice-6" class="size-4" />

                    <span>{{ $t('boards.roll_limit', { limit: board.diceRollLimit }) }}</span>
                  </div>

                  <div v-if="access" class="flex items-center gap-2 text-sm text-muted">
                    <u-icon :name="access.icon" class="size-4" />

                    <span>{{ $t(access.key) }}</span>
                  </div>

                  <!-- Team boards share one board per team, so this changes how
                       the board plays, not just who can join. -->
                  <div
                    v-if="board.mode === 'TEAM'"
                    class="flex items-center gap-2 text-sm text-muted"
                  >
                    <u-icon name="i-lucide-users" class="size-4" />

                    <span>{{ $t('boards.team_mode') }}</span>
                  </div>

                  <div class="flex items-center gap-2 mt-1">
                    <div class="flex -space-x-2">
                      <u-avatar
                        v-for="author in board.authors.slice(0, 3)"
                        :key="author.id"
                        :src="author.user.avatarUrl ?? undefined"
                        :alt="author.user.nickname ?? author.user.discordUsername"
                        size="xs"
                        class="ring-2 ring-background"
                      />
                    </div>

                    <span class="text-xs text-muted">
                      {{
                        board.authors.map(a => a.user.nickname ?? a.user.discordUsername).join(', ')
                      }}
                    </span>
                  </div>
                </div>
              </template>

              <template #footer>
                <u-button
                  variant="ghost"
                  color="primary"
                  trailing-icon="i-lucide-arrow-right"
                  size="sm"
                  :label="$t('boards.play')"
                />
              </template>
            </u-page-card>
          </nuxt-link>
        </div>
      </u-container>
    </u-page-body>

    <board-settings-modal v-model:open="showCreateModal" :board-id="null" @saved="onBoardCreated" />
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useBoards } from '~/composables/useBoards';
import { useAuthStore } from '~/stores/auth';
import {
  formatDate,
  formatBoardSize,
  boardEventStatus,
  BOARD_ACCESS_META,
  BOARD_STATUS_STYLE,
} from '~/utils/board';

const authStore = useAuthStore();

const { boards, pending, error, refresh } = await useBoards();

const showCreateModal = ref(false);

// Resolve each card's badges once rather than recomputing them per binding in
// the template. An unknown access mode yields no badge rather than a broken
// one, should the backend gain a mode the frontend does not know yet.
const decoratedBoards = computed(() =>
  boards.value.map(board => ({
    board,
    status: BOARD_STATUS_STYLE[boardEventStatus(board.startDate, board.endDate)],
    access: board.accessMode ? BOARD_ACCESS_META[board.accessMode] : undefined,
  })),
);

// A new board is only listed here when isListed is on, so refresh rather than
// assuming it will appear.
async function onBoardCreated() {
  await refresh();
}
</script>
