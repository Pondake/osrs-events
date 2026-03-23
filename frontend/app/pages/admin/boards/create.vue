<template>
  <nuxt-layout :title="$t('admin.create_board')">
    <u-page-body>
      <u-container class="max-w-2xl">
        <u-card class="osrs-border">
          <board-settings-form
            v-model="form"
            :current-user-id="authStore.user?.id"
            @add-team="onAddTeam"
            @remove-team="onRemoveTeam"
          />

          <!-- Form actions -->
          <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-default">
            <u-button to="/admin/boards" color="neutral" variant="ghost" :label="$t('common.cancel')" />
            <u-button
              color="primary"
              :loading="submitting"
              icon="i-lucide-check"
              :label="$t('common.create')"
              @click="onSubmit"
            />
          </div>
        </u-card>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { today, getLocalTimeZone } from '@internationalized/date'
import { useAuthStore } from '~/stores/auth'
import { createBoard } from '~/composables/useBoards'
import type { BoardFormData } from '~/components/Board/SettingsForm.vue'

definePageMeta({ middleware: ['admin'] })

const { t } = useI18n()
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

const todayDate = today(getLocalTimeZone())

const form = ref<BoardFormData>({
  title: '',
  description: '',
  size: 'SIZE_7X7',
  mode: 'SOLO',
  diceRollLimit: 3,
  unlimitedRolls: false,
  selectedAuthors: authStore.user ? [authStore.user] : [],
  assignedTeams: [],
  startDate: todayDate,
  endDate: todayDate.add({ months: 1 }),
})

const submitting = ref(false)

function toISO(d: any): string | null {
  return d ? `${d.toString()}T00:00:00.000Z` : null
}

async function onSubmit() {
  if (!form.value.title.trim()) {
    toast.add({ title: t('validation.title_required'), color: 'error' })
    return
  }

  submitting.value = true
  try {
    const input = {
      title: form.value.title.trim(),
      description: form.value.description.trim() || undefined,
      size: form.value.size,
      mode: form.value.mode,
      startDate: toISO(form.value.startDate),
      endDate: toISO(form.value.endDate),
      diceRollLimit: form.value.unlimitedRolls ? null : form.value.diceRollLimit,
      authorIds: form.value.selectedAuthors.map(a => a.id),
    }

    const board = await createBoard(input)
    toast.add({ title: t('admin.board_created'), color: 'success' })
    router.push(`/boards/${board.id}`)
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error' })
  } finally {
    submitting.value = false
  }
}

// Team management is only applicable for edit mode (board must exist first)
function onAddTeam(_teamId: string) {
  toast.add({ title: t('admin.save_first'), color: 'warning' })
}

function onRemoveTeam(_teamId: string) {
  // No-op in create mode
}
</script>
