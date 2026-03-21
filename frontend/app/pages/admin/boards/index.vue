<template>
  <nuxt-layout :title="$t('admin.boards_title')" :description="$t('admin.boards_subtitle')">
    <u-page-body>
      <u-container>
        <!-- Toolbar: search + create -->
        <div class="flex gap-3 mb-6">
          <u-input
            v-model="searchQuery"
            :placeholder="$t('common.search')"
            icon="i-heroicons-magnifying-glass"
            class="flex-1"
          />

          <u-button to="/admin/boards/create" icon="i-heroicons-plus" color="primary" :label="$t('admin.create_board')"/>
        </div>

        <div v-if="pending" class="flex justify-center py-12">
          <u-skeleton class="h-8 w-64" />
        </div>

        <u-alert
          v-else-if="error"
          color="error"
          icon="i-heroicons-exclamation-circle"
          :title="$t('errors.generic')"
        />

        <div v-else-if="boards.length === 0" class="text-center py-12 text-muted">
          <u-icon name="i-heroicons-squares-2x2" class="text-5xl mb-4 block mx-auto" />

          <p>{{ $t('boards.no_boards') }}</p>
        </div>

        <div v-else class="space-y-4">
          <u-blog-post v-for="board in filteredBoards" :key="board.id" class="osrs-border" :to="`/boards/${board.id}`">
            <template #title>
               <div class="flex items-center gap-3">
                  <h3 class="text-lg font-semibold osrs-font truncate">{{ board.title }}</h3>
                  <u-badge color="primary" variant="subtle" :label="$t('boards.size', { size: formatSize(board.size) })" />
                  <u-badge v-if="board.diceRollLimit" color="warning" variant="subtle" :label="$t('boards.roll_limit', { limit: board.diceRollLimit })"/>
                </div>
            </template>
            <template #description>
            <div class="flex items-center justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="text-sm text-muted flex flex-wrap gap-4">
                  <span v-if="board.startDate">
                    <u-icon name="i-heroicons-calendar" class="inline mr-1" />
                    {{ $t('boards.starts') }}: {{ formatDate(board.startDate) }}
                  </span>

                  <span v-if="board.endDate">
                    <u-icon name="i-heroicons-calendar" class="inline mr-1" />
                    {{ $t('boards.ends') }}: {{ formatDate(board.endDate) }}
                  </span>

                  <span>
                    <u-icon name="i-heroicons-user-group" class="inline mr-1" />
                    {{
                      board.authors
                        .map((a: any) => a.user.nickname || a.user.discordUsername)
                        .join(', ')
                    }}
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <u-button
                  :to="`/boards/${board.id}`"
                  icon="i-heroicons-eye"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                />

                <u-button
                  icon="i-heroicons-trash"
                  color="error"
                  variant="ghost"
                  size="sm"
                  :loading="deletingId === board.id"
                  @click="confirmDelete(board)"
                />
              </div>
            </div>
            </template>
          </u-blog-post>
        </div>
      </u-container>
    </u-page-body>

    <!-- Delete confirmation modal -->
    <u-modal v-model:open="showDeleteModal">
      <template #content>
        <u-card>
          <template #header>
            <h3 class="text-lg font-semibold">{{ $t('common.delete') }}</h3>
          </template>

          <p>
            Are you sure you want to delete <strong>{{ boardToDelete?.title }}</strong
            >? This cannot be undone.
          </p>

          <template #footer>
            <div class="flex justify-end gap-2">
              <u-button color="neutral" variant="ghost" @click="showDeleteModal = false">
                {{ $t('common.cancel') }}
              </u-button>

              <u-button color="error" :loading="deletingId !== null" @click="doDelete">
                {{ $t('common.delete') }}
              </u-button>
            </div>
          </template>
        </u-card>
      </template>
    </u-modal>
  </nuxt-layout>
</template>

<script setup lang="ts">
definePageMeta({ middleware: ['admin'] });

const { t } = useI18n();
const toast = useToast();

const BOARDS_QUERY = `
  query AdminBoards {
    boards {
      id
      title
      size
      startDate
      endDate
      diceRollLimit
      authors {
        user { id discordUsername nickname }
      }
    }
  }
`;

const DELETE_BOARD_MUTATION = `
  mutation DeleteBoard($id: String!) {
    deleteBoard(id: $id) { id }
  }
`;

const SIZE_DISPLAY: Record<string, string> = {
  SIZE_5X5: '5×5',
  SIZE_7X7: '7×7',
  SIZE_9X9: '9×9',
};

function formatSize(size: string): string {
  return SIZE_DISPLAY[size] ?? size;
}

const { data, pending, error, refresh } = await useGql<{ boards: any[] }>(BOARDS_QUERY);
const boards = computed(() => data.value?.boards ?? []);

const searchQuery = ref('');
const filteredBoards = computed(() => {
  if (!searchQuery.value.trim()) return boards.value;
  const q = searchQuery.value.toLowerCase();
  return boards.value.filter((b: any) => b.title.toLowerCase().includes(q));
});

const showDeleteModal = ref(false);
const boardToDelete = ref<any>(null);
const deletingId = ref<string | null>(null);

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function confirmDelete(board: any) {
  boardToDelete.value = board;
  showDeleteModal.value = true;
}

async function doDelete() {
  if (!boardToDelete.value) return;
  deletingId.value = boardToDelete.value.id;
  try {
    await useGqlMutation(DELETE_BOARD_MUTATION, { id: boardToDelete.value.id });
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
