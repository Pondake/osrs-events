<template>
  <nuxt-layout :title="$t('boards.title')" :description="$t('boards.subtitle')">
    <template v-if="authStore.isAdmin || authStore.isEditor" #links>
      <u-button
        color="primary"
        icon="i-lucide-plus"
        to="/admin/boards/create"
        :label="$t('admin.create_board')"
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
          <nuxt-link v-for="board in boards" :key="board.id" :to="`/boards/${board.id}`">
            <u-page-card
              :title="board.title"
              class="h-full hover:border-primary transition-colors cursor-pointer"
            >
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

                  <div class="flex gap-1 mt-1">
                    <u-badge
                      v-if="(board as any).accessMode === 'GUILD'"
                      color="info"
                      variant="subtle"
                      size="xs"
                      icon="i-lucide-shield"
                      :label="$t('boards.access_server')"
                    />

                    <u-badge
                      v-else-if="(board as any).accessMode === 'INVITE'"
                      color="warning"
                      variant="subtle"
                      size="xs"
                      icon="i-lucide-lock"
                      :label="$t('boards.access_invite')"
                    />
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
  </nuxt-layout>
</template>

<script setup lang="ts">
import { useBoards } from '~/composables/useBoards';
import { useAuthStore } from '~/stores/auth';
import { formatDate, formatBoardSize } from '~/utils/board';

const authStore = useAuthStore();

const { boards, pending, error } = await useBoards();
</script>
