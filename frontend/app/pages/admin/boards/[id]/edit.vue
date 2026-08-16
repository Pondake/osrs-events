<template>
  <nuxt-layout :title="$t('admin.edit_board')">
    <u-page-body>
      <u-container class="max-w-2xl">
        <div v-if="boardPending" class="flex justify-center py-12">
          <u-skeleton class="h-8 w-64" />
        </div>

        <u-alert
          v-else-if="boardError || !board"
          color="error"
          icon="i-lucide-alert-circle"
          :title="$t('errors.generic')"
        />

        <u-card v-else class="osrs-border">
          <u-form :state="form" :schema="schema" @submit="onSubmit">
            <div class="space-y-6">
              <!-- Board title -->
              <u-form-field
                :label="$t('admin.board_title')"
                :description="$t('admin.board_title_desc')"
                name="title"
                required
              >
                <u-input
                  v-model="form.title"
                  :placeholder="$t('admin.board_title_placeholder')"
                  class="w-full"
                />
              </u-form-field>

              <!-- Board description -->
              <u-form-field
                :label="$t('admin.board_description')"
                :description="$t('admin.board_description_desc')"
                name="description"
              >
                <u-textarea
                  v-model="form.description"
                  :placeholder="$t('admin.board_description_placeholder')"
                  class="w-full"
                  :rows="3"
                />
              </u-form-field>

              <!-- Board size -->
              <u-form-field
                :label="$t('admin.board_size')"
                :description="$t('admin.board_size_desc')"
                name="size"
                required
              >
                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
              </u-form-field>

              <!-- Date range -->
              <u-form-field
                :label="$t('admin.date_range')"
                :description="$t('admin.date_range_desc')"
                name="dateRange"
              >
                <u-input-date ref="inputDate" v-model="dateRange" range locale="nl" class="w-full">
                  <template #trailing>
                    <u-popover :reference="inputDate?.inputsRef?.[0]?.$el">
                      <u-button
                        color="neutral"
                        variant="link"
                        size="sm"
                        icon="i-lucide-calendar"
                        :aria-label="$t('admin.date_range')"
                        class="px-0"
                      />
                      <template #content>
                        <u-calendar v-model="dateRange" class="p-2" :number-of-months="2" range />
                      </template>
                    </u-popover>
                  </template>
                </u-input-date>
              </u-form-field>

              <!-- Dice roll limit -->
              <u-form-field
                :label="$t('admin.dice_roll_limit')"
                :description="$t('admin.dice_roll_limit_desc')"
                name="diceRollLimit"
              >
                <div class="flex items-center gap-3">
                  <u-input
                    v-model.number="form.diceRollLimit"
                    type="number"
                    min="1"
                    max="99"
                    :disabled="form.unlimitedRolls"
                    class="w-32"
                  />
                  <u-checkbox
                    v-model="form.unlimitedRolls"
                    :label="$t('admin.dice_roll_unlimited')"
                  />
                </div>
              </u-form-field>

              <!-- Co-editors (non-owner authors) -->
              <u-form-field
                :label="$t('admin.editors')"
                :description="$t('admin.editors_desc')"
                name="editors"
              >
                <div class="space-y-2">
                  <!-- Current authors -->
                  <div v-if="board.authors.length > 0" class="flex flex-wrap gap-2">
                    <u-badge
                      v-for="author in board.authors"
                      :key="author.id"
                      :color="author.isOwner ? 'primary' : 'neutral'"
                      variant="subtle"
                      class="flex items-center gap-1"
                    >
                      <u-icon v-if="author.isOwner" name="i-lucide-crown" class="w-3 h-3 mr-0.5" />
                      {{ author.user.nickname ?? author.user.discordUsername }}
                      <button
                        v-if="!author.isOwner"
                        type="button"
                        class="ml-1 hover:text-red-400"
                        :disabled="removingAuthorId === author.user.id"
                        @click="doRemoveAuthor(author.user.id)"
                      >
                        <u-icon name="i-lucide-x" class="w-3 h-3" />
                      </button>
                    </u-badge>
                  </div>

                  <!-- Search to add co-editors -->
                  <div class="flex gap-2">
                    <u-input
                      v-model="authorSearch"
                      :placeholder="$t('common.search')"
                      class="flex-1"
                      @input="onAuthorSearch"
                    />
                  </div>

                  <!-- Search results -->
                  <div
                    v-if="userResults.length > 0"
                    class="border border-muted rounded-lg overflow-hidden"
                  >
                    <button
                      v-for="user in userResults"
                      :key="user.id"
                      type="button"
                      class="w-full flex items-center gap-3 px-3 py-2 hover:bg-muted transition-colors text-left"
                      @click="doAddAuthor(user)"
                    >
                      <u-avatar
                        :src="user.avatarUrl ?? undefined"
                        :alt="user.discordUsername"
                        size="xs"
                      />
                      <span>{{ user.discordUsername }}</span>
                    </button>
                  </div>
                </div>
              </u-form-field>

              <!-- Form actions -->
              <div class="flex justify-end gap-3 pt-4">
                <u-button to="/admin/boards" color="neutral" variant="ghost" :label="$t('common.cancel')" />
                <u-button
                  type="submit"
                  color="primary"
                  :loading="submitting"
                  icon="i-lucide-check"
                  :label="$t('common.save')"
                />
              </div>
            </div>
          </u-form>
        </u-card>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { parseDate } from '@internationalized/date'
import * as z from 'zod'
import type { CalendarDate } from '@internationalized/date'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { UserEntity } from '~/types/graphql'
import { useBoard, updateBoard, addBoardAuthor, removeBoardAuthor } from '~/composables/useBoards'
import { fetchUsers } from '~/composables/useUsers'

definePageMeta({ middleware: ['admin'] })

const route = useRoute()
const boardId = route.params.id as string

const { t } = useI18n()
const toast = useToast()

// ─── Load existing board ───────────────────────────────────────────────────────

const { board, pending: boardPending, error: boardError, refresh: refreshBoard } = await useBoard(boardId)

const schema = z.object({
  title: z.string().min(1, t('validation.title_required')).max(100, t('validation.title_too_long')),
  description: z.string().max(500, t('validation.desc_too_long')).optional().or(z.literal('')),
  size: z.enum(['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9']),
})
type Schema = z.output<typeof schema>

const sizeOptions = [
  { label: '5×5 (25 tiles)', value: 'SIZE_5X5' },
  { label: '7×7 (49 tiles)', value: 'SIZE_7X7' },
  { label: '9×9 (81 tiles)', value: 'SIZE_9X9' },
]

// ─── Form state (initialised from loaded board) ────────────────────────────────

const form = reactive({
  title: '',
  description: '',
  size: 'SIZE_7X7' as 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9',
  diceRollLimit: 3,
  unlimitedRolls: false,
})

const inputDate = useTemplateRef('inputDate')
// The calendar components model an absent bound as undefined, not null, and
// DateRange requires both keys to be present even when their value is undefined.
const dateRange = shallowRef<{ start: CalendarDate | undefined, end: CalendarDate | undefined }>({
  start: undefined,
  end: undefined,
})

// Populate form once the board loads
watch(board, (b) => {
  if (!b) return
  form.title       = b.title
  form.description = b.description ?? ''
  form.size        = b.size as 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9'
  form.diceRollLimit  = b.diceRollLimit ?? 3
  form.unlimitedRolls = b.diceRollLimit == null

  dateRange.value = {
    start: b.startDate ? parseDate(b.startDate.slice(0, 10)) : undefined,
    end:   b.endDate   ? parseDate(b.endDate.slice(0, 10))   : undefined,
  }
}, { immediate: true })

// ─── Author management ────────────────────────────────────────────────────────

type AuthorOption = Pick<UserEntity, 'id' | 'discordUsername' | 'avatarUrl'>

const authorSearch   = ref('')
const userResults    = ref<AuthorOption[]>([])
const removingAuthorId = ref<string | null>(null)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onAuthorSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (authorSearch.value.length < 2) {
    userResults.value = []
    return
  }
  searchTimeout = setTimeout(async () => {
    try {
      const currentAuthorIds = (board.value?.authors ?? []).map((a) => a.user.id)
      const results = await fetchUsers(authorSearch.value)
      userResults.value = results.filter((u) => !currentAuthorIds.includes(u.id))
    } catch {
      userResults.value = []
    }
  }, 300)
}

async function doAddAuthor(user: AuthorOption) {
  userResults.value = []
  authorSearch.value = ''
  try {
    await addBoardAuthor(boardId, user.id)
    toast.add({id: `board-${boardId}`, title: t('admin.editor_added', { name: user.discordUsername }), color: 'success' })
    await refreshBoard()
  } catch {
    toast.add({id: `board-${boardId}`, title: t('errors.generic'), color: 'error' })
  }
}

async function doRemoveAuthor(userId: string) {
  removingAuthorId.value = userId
  try {
    await removeBoardAuthor(boardId, userId)
    toast.add({id: `board-${boardId}`, title: t('admin.editor_removed'), color: 'neutral' })
    await refreshBoard()
  } catch {
    toast.add({id: `board-${boardId}`, title: t('errors.generic'), color: 'error' })
  } finally {
    removingAuthorId.value = null
  }
}

// ─── Submit ───────────────────────────────────────────────────────────────────

const submitting = ref(false)

async function onSubmit(_event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    const toISO = (d?: CalendarDate) => (d ? `${d.toString()}T00:00:00.000Z` : null)

    const input = {
      title:          form.title.trim(),
      description:    form.description.trim() || null,
      size:           form.size,
      startDate:      toISO(dateRange.value.start),
      endDate:        toISO(dateRange.value.end),
      diceRollLimit:  form.unlimitedRolls ? null : form.diceRollLimit,
    }
    await updateBoard(boardId, input)
    toast.add({id: `board-${boardId}`, title: t('admin.board_updated'), color: 'success' })
    await refreshBoard()
  } catch {
    toast.add({id: `board-${boardId}`, title: t('errors.generic'), color: 'error' })
  } finally {
    submitting.value = false
  }
}
</script>
