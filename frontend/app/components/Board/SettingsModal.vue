<template>
  <u-modal
    :open="open"
    :title="boardId ? $t('admin.edit_board') : $t('admin.create_board')"
    :ui="{ content: 'max-w-2xl' }"
    @update:open="$emit('update:open', $event)"
  >
    <template #body>
      <board-settings-form
        v-model="form"
        :current-user-id="authStore.user?.id"
        @add-team="onAddTeam"
        @remove-team="onRemoveTeam"
      />
    </template>

    <template #footer>
      <div class="flex justify-end gap-3 w-full">
        <u-button
          color="neutral"
          variant="ghost"
          :label="$t('common.cancel')"
          @click="$emit('update:open', false)"
        />
        <u-button
          color="primary"
          icon="i-lucide-check"
          :loading="saving"
          :label="boardId ? $t('common.save') : $t('common.create')"
          @click="onSave"
        />
      </div>
    </template>
  </u-modal>
</template>

<script setup lang="ts">
import { today, getLocalTimeZone, parseDate } from '@internationalized/date'
import type { CalendarDate } from '@internationalized/date'
import { useAuthStore } from '~/stores/auth'
import { createBoard, updateBoard, addTeamToBoard, removeTeamFromBoard } from '~/composables/useBoards'
import type { BoardEntity } from '~/types/graphql'
import type { BoardFormData, AssignedTeam } from './SettingsForm.vue'

const props = defineProps<{
  open: boolean
  boardId?: string | null
  initialData?: Partial<BoardFormData>
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  'saved': [board: BoardEntity]
}>()

const authStore = useAuthStore()
const toast = useToast()
const { t } = useI18n()

const todayDate = today(getLocalTimeZone())

function buildDefaultForm(): BoardFormData {
  return {
    title: props.initialData?.title ?? '',
    description: props.initialData?.description ?? '',
    size: props.initialData?.size ?? 'SIZE_7X7',
    mode: props.initialData?.mode ?? 'SOLO',
    diceRollLimit: props.initialData?.diceRollLimit ?? 3,
    unlimitedRolls: props.initialData?.unlimitedRolls ?? false,
    selectedAuthors: props.initialData?.selectedAuthors ?? (authStore.user ? [authStore.user] : []),
    assignedTeams: props.initialData?.assignedTeams ?? [],
    startDate: props.initialData?.startDate ?? todayDate,
    endDate: props.initialData?.endDate ?? todayDate.add({ months: 1 }),
  }
}

const form = ref<BoardFormData>(buildDefaultForm())

// Reset form when modal opens with new data
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.value = buildDefaultForm()
  }
})

const saving = ref(false)

function toISO(d: CalendarDate | null): string | null {
  return d ? `${d.toString()}T00:00:00.000Z` : null
}

async function onSave() {
  if (!form.value.title.trim()) {
    toast.add({ title: t('validation.title_required'), color: 'error' })
    return
  }

  saving.value = true
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

    let board: BoardEntity

    if (props.boardId) {
      board = await updateBoard(props.boardId, input)
      toast.add({ title: t('admin.board_updated'), color: 'success' })
    } else {
      // Create the board first, then assign any queued teams sequentially
      board = await createBoard(input)
      for (const bt of form.value.assignedTeams) {
        await addTeamToBoard(board.id, bt.teamId)
      }
      toast.add({ title: t('admin.board_created'), color: 'success' })
    }

    emit('saved', board)
    emit('update:open', false)
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    saving.value = false
  }
}

/** Add a team — optimistic local update when creating, API call when editing. */
async function onAddTeam(team: { id: string; name: string; iconUrl: string | null }) {
  // Prevent duplicates
  if (form.value.assignedTeams.some(bt => bt.teamId === team.id)) return

  if (!props.boardId) {
    // Creating a new board: just queue locally — teams will be assigned after save
    form.value = {
      ...form.value,
      assignedTeams: [
        ...form.value.assignedTeams,
        { teamId: team.id, team: { id: team.id, name: team.name, iconUrl: team.iconUrl } },
      ],
    }
    return
  }

  // Editing an existing board: call the API immediately
  try {
    const result = await addTeamToBoard(props.boardId, team.id)
    form.value = {
      ...form.value,
      assignedTeams: [
        ...form.value.assignedTeams,
        { teamId: result.teamId, team: result.team },
      ],
    }
    toast.add({ title: t('admin.team_added'), color: 'success' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  }
}

/** Remove a team — optimistic local update when creating, API call when editing. */
async function onRemoveTeam(teamId: string) {
  if (!props.boardId) {
    // Creating a new board: just remove from local queue
    form.value = {
      ...form.value,
      assignedTeams: form.value.assignedTeams.filter(bt => bt.teamId !== teamId),
    }
    return
  }

  // Editing an existing board: call the API
  try {
    await removeTeamFromBoard(props.boardId, teamId)
    form.value = {
      ...form.value,
      assignedTeams: form.value.assignedTeams.filter(bt => bt.teamId !== teamId),
    }
    toast.add({ title: t('admin.team_removed'), color: 'success' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  }
}
</script>
