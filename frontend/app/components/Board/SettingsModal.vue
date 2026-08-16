<template>
  <u-modal
    :open="open"
    :title="boardId ? $t('admin.edit_board') : $t('admin.create_board')"
    :ui="{ content: 'max-w-2xl' }"
    @update:open="$emit('update:open', $event)"
  >
    <!-- CREATE MODE: stepper -->
    <template v-if="!boardId" #body>
      <u-stepper
        ref="stepper"
        v-model="currentStep"
        linear
        :items="steps"
        class="mb-8"
      />

      <div v-show="currentStep === 0"><board-form-basics-section v-model="form" /></div>
      <div v-show="currentStep === 1"><board-form-schedule-section v-model="form" /></div>
      <div v-show="currentStep === 2"><board-form-access-section v-model="form" /></div>
      <div v-show="currentStep === 3">
        <board-form-editors-section
          v-model="form"
          :current-user-id="authStore.user?.id"
          :board-id="null"
          @add-team="onAddTeam"
          @remove-team="onRemoveTeam"
        />
      </div>
    </template>

    <!-- EDIT MODE: tabs -->
    <template v-else #body>
      <u-tabs :items="tabItems">
        <template #basics><board-form-basics-section v-model="form" /></template>
        <template #schedule><board-form-schedule-section v-model="form" /></template>
        <template #access><board-form-access-section v-model="form" /></template>
        <template #editors>
          <board-form-editors-section
            v-model="form"
            :current-user-id="authStore.user?.id"
            :board-id="boardId"
            @add-team="onAddTeam"
            @remove-team="onRemoveTeam"
          />
        </template>
        <template #invites>
          <board-invite-manager :board-id="boardId!" />
        </template>
      </u-tabs>
    </template>

    <!-- CREATE footer: back / next / create -->
    <template v-if="!boardId" #footer>
      <div class="flex justify-between gap-3 w-full">
        <u-button
          color="neutral"
          variant="ghost"
          icon="i-lucide-arrow-left"
          :label="$t('common.back')"
          :disabled="currentStep === 0"
          @click="stepperRef?.prev()"
        />
        <div class="flex gap-3">
          <u-button
            color="neutral"
            variant="ghost"
            :label="$t('common.cancel')"
            @click="requestCancel()"
          />
          <u-button
            v-if="currentStep < steps.length - 1"
            color="primary"
            trailing-icon="i-lucide-arrow-right"
            :label="$t('common.next')"
            @click="tryNext()"
          />
          <u-button
            v-else
            color="primary"
            icon="i-lucide-check"
            :loading="saving"
            :label="$t('common.create')"
            @click="onSave"
          />
        </div>
      </div>
    </template>

    <!-- EDIT footer: cancel / save -->
    <template v-else #footer>
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
          :label="$t('common.save')"
          @click="onSave"
        />
      </div>
    </template>
  </u-modal>

  <!-- Cancel confirmation (create mode only, shown when form is dirty) -->
  <u-modal v-model:open="showCancelConfirm" :title="$t('admin.cancel_create_title')">
    <template #body>
      <p class="text-sm text-muted">{{ $t('admin.cancel_create_desc') }}</p>
    </template>
    <template #footer>
      <div class="flex justify-end gap-3 w-full">
        <u-button color="neutral" variant="ghost" :label="$t('admin.cancel_create_keep')" @click="showCancelConfirm = false" />
        <u-button color="error" :label="$t('admin.cancel_create_discard')" @click="confirmDiscard()" />
      </div>
    </template>
  </u-modal>
</template>

<script setup lang="ts">
import { today, getLocalTimeZone } from '@internationalized/date'
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

// ─── Stepper (create mode) ────────────────────────────────────────────────────

const currentStep = ref(0)
const stepperRef = useTemplateRef('stepper')

const steps = computed(() => [
  { title: t('admin.step_basics'), description: t('admin.step_basics_desc'), icon: 'i-lucide-layout-grid' },
  { title: t('admin.step_schedule'), description: t('admin.step_schedule_desc'), icon: 'i-lucide-calendar' },
  { title: t('admin.step_access'), description: t('admin.step_access_desc'), icon: 'i-lucide-lock' },
  { title: t('admin.step_editors'), description: t('admin.step_editors_desc'), icon: 'i-lucide-users' },
])

function validateStep(step: number): string | null {
  if (step === 0 && !form.value.title.trim()) return t('validation.title_required')
  if (step === 2 && form.value.accessMode === 'GUILD' && !form.value.requiredGuildId) return t('validation.server_required')
  return null
}

function tryNext() {
  const error = validateStep(currentStep.value)
  if (error) {
    toast.add({ title: error, color: 'error', id: 'step-validation' })
    return
  }
  stepperRef.value?.next()
}

// ─── Tabs (edit mode) ─────────────────────────────────────────────────────────

type TabSlot = 'basics' | 'schedule' | 'access' | 'editors' | 'invites'

const tabItems = computed<{ label: string, icon: string, slot: TabSlot }[]>(() => {
  const items: { label: string, icon: string, slot: TabSlot }[] = [
    { label: t('admin.step_basics'), icon: 'i-lucide-layout-grid', slot: 'basics' },
    { label: t('admin.step_schedule'), icon: 'i-lucide-calendar', slot: 'schedule' },
    { label: t('admin.step_access'), icon: 'i-lucide-lock', slot: 'access' },
    { label: t('admin.step_editors'), icon: 'i-lucide-users', slot: 'editors' },
  ]
  if (props.boardId && form.value.accessMode === 'INVITE') {
    items.push({ label: t('admin.invite_links'), icon: 'i-lucide-link', slot: 'invites' })
  }
  return items
})

// ─── Form ─────────────────────────────────────────────────────────────────────

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
    startDate: props.initialData?.startDate ?? todayDate.toString(),
    endDate: props.initialData?.endDate ?? todayDate.add({ months: 1 }).toString(),
    isListed: props.initialData?.isListed ?? true,
    accessMode: props.initialData?.accessMode ?? 'OPEN',
    requiredGuildId: props.initialData?.requiredGuildId ?? null,
  }
}

const form = ref<BoardFormData>(buildDefaultForm())

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.value = buildDefaultForm()
    currentStep.value = 0
  }
})

const saving = ref(false)

// ─── Cancel confirmation (create mode) ───────────────────────────────────────

const showCancelConfirm = ref(false)

const isDirty = computed(() =>
  form.value.title.trim() !== '' ||
  form.value.description.trim() !== '' ||
  form.value.accessMode !== 'OPEN' ||
  !form.value.isListed ||
  form.value.selectedAuthors.length > 1 ||
  form.value.assignedTeams.length > 0
)

function requestCancel() {
  if (!props.boardId && isDirty.value) {
    showCancelConfirm.value = true
  } else {
    emit('update:open', false)
  }
}

function confirmDiscard() {
  showCancelConfirm.value = false
  emit('update:open', false)
}

// Form holds YYYY-MM-DD; the API expects a full ISO timestamp.
function toISO(d: string | null): string | null {
  return d ? `${d}T00:00:00.000Z` : null
}

async function onSave() {
  if (!form.value.title.trim()) {
    toast.add({ title: t('validation.title_required'), color: 'error', id: 'step-validation' })
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
      isListed: form.value.isListed,
      accessMode: form.value.accessMode,
      requiredGuildId: form.value.requiredGuildId ?? undefined,
    }

    let board: BoardEntity

    if (props.boardId) {
      board = await updateBoard(props.boardId, input)
      toast.add({ title: t('admin.board_updated'), color: 'success', id: 'board-save' })
    } else {
      board = await createBoard(input)
      for (const bt of form.value.assignedTeams) {
        await addTeamToBoard(board.id, bt.teamId)
      }
      toast.add({ title: t('admin.board_created'), color: 'success', id: 'board-save' })
    }

    emit('saved', board)
    emit('update:open', false)
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error', id: 'board-save-error' })
  } finally {
    saving.value = false
  }
}

async function onAddTeam(team: { id: string; name: string; iconUrl: string | null }) {
  if (form.value.assignedTeams.some(bt => bt.teamId === team.id)) return

  if (!props.boardId) {
    form.value = {
      ...form.value,
      assignedTeams: [
        ...form.value.assignedTeams,
        { teamId: team.id, team: { id: team.id, name: team.name, iconUrl: team.iconUrl } },
      ],
    }
    return
  }

  try {
    const result = await addTeamToBoard(props.boardId, team.id)
    form.value = {
      ...form.value,
      assignedTeams: [...form.value.assignedTeams, { teamId: result.teamId, team: result.team }],
    }
    toast.add({ title: t('admin.team_added'), color: 'success', id: 'team-update' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error', id: 'team-update-error' })
  }
}

async function onRemoveTeam(teamId: string) {
  if (!props.boardId) {
    form.value = {
      ...form.value,
      assignedTeams: form.value.assignedTeams.filter(bt => bt.teamId !== teamId),
    }
    return
  }

  try {
    await removeTeamFromBoard(props.boardId, teamId)
    form.value = {
      ...form.value,
      assignedTeams: form.value.assignedTeams.filter(bt => bt.teamId !== teamId),
    }
    toast.add({ title: t('admin.team_removed'), color: 'neutral', id: 'team-update' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error', id: 'team-update-error' })
  }
}
</script>
