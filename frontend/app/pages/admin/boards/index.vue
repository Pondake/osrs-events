<template>
  <nuxt-layout :title="$t('admin.boards_title')" :description="$t('admin.boards_subtitle')">
    <u-page-body>
      <u-container>
        <div class="flex gap-3 mb-6">
          <u-input
            v-model="searchQuery"
            :placeholder="$t('common.search')"
            icon="i-lucide-search"
            class="flex-1"
          />

          <u-button
            icon="i-lucide-plus"
            color="primary"
            :label="$t('admin.create_board')"
            @click="openCreate()"
          />
        </div>

        <div v-if="pending" class="flex justify-center py-12">
          <u-skeleton class="h-8 w-64" />
        </div>

        <u-alert
          v-else-if="error"
          color="error"
          icon="i-lucide-alert-circle"
          :title="$t('errors.generic')"
        />

        <div v-else-if="filteredBoards.length === 0" class="text-center py-12 text-muted">
          <u-icon name="i-lucide-layout-grid" class="text-5xl mb-4 block mx-auto" />

          <p>{{ $t('boards.no_boards') }}</p>
        </div>

        <div v-else class="space-y-4">
          <u-blog-post
            v-for="board in filteredBoards"
            :key="board.id"
            class="osrs-border"
            :to="`/boards/${board.id}`"
          >
            <template #title>
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold osrs-font truncate">{{ board.title }}</h3>

                <u-badge
                  color="primary"
                  variant="subtle"
                  :label="$t('boards.size', { size: formatBoardSize(board.size) })"
                />

                <u-badge
                  v-if="board.mode === 'TEAM'"
                  color="warning"
                  variant="subtle"
                  :label="$t('boards.team_mode')"
                />

                <u-badge
                  v-if="board.diceRollLimit"
                  color="neutral"
                  variant="subtle"
                  :label="$t('boards.roll_limit', { limit: board.diceRollLimit })"
                />

                <u-badge
                  v-if="accessMeta(board.accessMode)"
                  color="neutral"
                  variant="subtle"
                  :icon="accessMeta(board.accessMode)!.icon"
                  :label="$t(accessMeta(board.accessMode)!.key)"
                />

                <!-- Unlike /boards, this list is unfiltered, so unlisted boards
                     appear here and need to be distinguishable. -->
                <u-badge
                  v-if="!board.isListed"
                  color="neutral"
                  variant="outline"
                  icon="i-lucide-eye-off"
                  :label="$t('boards.unlisted')"
                />
              </div>
            </template>

            <template #description>
              <div class="flex items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="text-sm text-muted flex flex-wrap gap-4">
                    <span v-if="board.startDate">
                      <u-icon name="i-lucide-calendar" class="inline mr-1" />
                      {{ $t('boards.starts') }}: {{ formatDate(board.startDate) }}
                    </span>

                    <span v-if="board.endDate">
                      <u-icon name="i-lucide-calendar" class="inline mr-1" />
                      {{ $t('boards.ends') }}: {{ formatDate(board.endDate) }}
                    </span>

                    <span>
                      <u-icon name="i-lucide-users" class="inline mr-1" />
                      {{
                        board.authors.map(a => a.user.nickname ?? a.user.discordUsername).join(', ')
                      }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <u-button
                    :to="`/boards/${board.id}`"
                    icon="i-lucide-eye"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                  />

                  <u-button
                    icon="i-lucide-settings"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    @click.prevent="openSettings(board)"
                  />

                  <u-button
                    icon="i-lucide-trash"
                    color="error"
                    variant="ghost"
                    size="sm"
                    :loading="deletingId === board.id"
                    @click.prevent="confirmDelete(board)"
                  />
                </div>
              </div>
            </template>
          </u-blog-post>
        </div>
      </u-container>
    </u-page-body>

    <!-- Settings modal -->
    <board-settings-modal
      :open="showSettingsModal"
      :board-id="editingBoardId"
      :initial-data="editingBoardData"
      @update:open="showSettingsModal = $event"
      @saved="onBoardSaved"
    />

    <!-- Delete confirmation modal -->
    <u-modal v-model:open="showDeleteModal">
      <template #content>
        <u-card>
          <template #header>
            <h3 class="text-lg font-semibold">{{ $t('common.delete') }}</h3>
          </template>

          <p>
            {{ $t('admin.delete_board_confirm', { title: boardToDelete?.title }) }}
          </p>

          <template #footer>
            <div class="flex justify-end gap-2">
              <u-button
                color="neutral"
                variant="ghost"
                :label="$t('common.cancel')"
                @click="showDeleteModal = false"
              />

              <u-button
                color="error"
                :loading="deletingId !== null"
                :label="$t('common.delete')"
                @click="doDelete"
              />
            </div>
          </template>
        </u-card>
      </template>
    </u-modal>
  </nuxt-layout>
</template>

<script setup lang="ts">
import type { BoardFormData } from '~/components/Board/SettingsForm.vue';
import type { BoardEntity } from '~/types/graphql';

import { useBoards } from '~/composables/useBoards';
import { formatDate, formatBoardSize, BOARD_ACCESS_META } from '~/utils/board';

definePageMeta({ middleware: ['admin'] });

const { t } = useI18n();
const toast = useToast();

const { boards, pending, error, refresh, deleteBoard } = await useBoards();

const searchQuery = ref('');
const filteredBoards = computed(() => {
  if (!searchQuery.value.trim()) return boards.value;
  const q = searchQuery.value.toLowerCase();
  return boards.value.filter(b => b.title.toLowerCase().includes(q));
});

// ─── Settings modal ───────────────────────────────────────────────────────────

function accessMeta(mode: string | null | undefined) {
  return mode ? BOARD_ACCESS_META[mode] : undefined;
}

const showSettingsModal = ref(false);
const editingBoardId = ref<string | null>(null);
const editingBoardData = ref<Partial<BoardFormData> | undefined>(undefined);

function openCreate() {
  editingBoardId.value = null;
  editingBoardData.value = undefined;
  showSettingsModal.value = true;
}

function openSettings(board: BoardEntity) {
  editingBoardId.value = board.id;
  editingBoardData.value = {
    title: board.title,
    description: board.description ?? '',
    size: board.size as 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9',
    mode: board.mode as 'SOLO' | 'TEAM',
    diceRollLimit: board.diceRollLimit ?? 3,
    unlimitedRolls: board.diceRollLimit === null,
    selectedAuthors: board.authors.map(a => ({
      id: a.user.id,
      discordUsername: a.user.discordUsername,
      avatarUrl: a.user.avatarUrl,
    })),
    assignedTeams: (board.boardTeams ?? []).map(bt => ({
      teamId: bt.teamId,
      team: bt.team,
    })),
    startDate: board.startDate?.toString().slice(0, 10) ?? null,
    endDate: board.endDate?.toString().slice(0, 10) ?? null,
    isListed: board.isListed,
    accessMode: board.accessMode as 'OPEN' | 'GUILD' | 'INVITE',
    requiredGuildId: board.requiredGuildId ?? null,
  };
  showSettingsModal.value = true;
}

async function onBoardSaved() {
  await refresh();
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const showDeleteModal = ref(false);
const boardToDelete = ref<BoardEntity | null>(null);
const deletingId = ref<string | null>(null);

function confirmDelete(board: BoardEntity) {
  boardToDelete.value = board;
  showDeleteModal.value = true;
}

async function doDelete() {
  if (!boardToDelete.value) return;
  deletingId.value = boardToDelete.value.id;
  try {
    await deleteBoard(boardToDelete.value.id);
    toast.add({ title: t('admin.board_deleted'), color: 'success' });
    showDeleteModal.value = false;
    boardToDelete.value = null;
    await refresh();
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error' });
  } finally {
    deletingId.value = null;
  }
}
</script>
