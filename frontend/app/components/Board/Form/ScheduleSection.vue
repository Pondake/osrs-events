<template>
  <div class="space-y-6">
    <!-- Date range -->
    <u-form-field
      :label="$t('admin.date_range')"
      :description="$t('admin.date_range_desc')"
      name="dateRange"
    >
      <u-input-date ref="inputDate" :model-value="dateRange" range locale="nl" class="w-full" @update:model-value="onDateRangeChange">
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
              <u-calendar :model-value="dateRange" class="p-2" :number-of-months="2" range @update:model-value="onDateRangeChange" />
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
          :model-value="modelValue.diceRollLimit"
          type="number"
          min="1"
          max="99"
          :disabled="modelValue.unlimitedRolls"
          class="w-32"
          @update:model-value="emit('update:modelValue', { ...modelValue, diceRollLimit: Number($event) })"
        />
        <u-checkbox
          :model-value="modelValue.unlimitedRolls"
          :label="$t('admin.dice_roll_unlimited')"
          @update:model-value="emit('update:modelValue', { ...modelValue, unlimitedRolls: $event })"
        />
      </div>
    </u-form-field>
  </div>
</template>

<script setup lang="ts">
import type { CalendarDate } from '@internationalized/date'
import type { BoardFormData } from '~/components/Board/SettingsForm.vue'

const props = defineProps<{
  modelValue: BoardFormData
}>()

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData]
}>()

const inputDate = useTemplateRef('inputDate')

const dateRange = computed(() => ({
  start: props.modelValue.startDate,
  end: props.modelValue.endDate,
}))

function onDateRangeChange(val: { start: CalendarDate | null; end: CalendarDate | null } | null) {
  if (!val) return
  emit('update:modelValue', {
    ...props.modelValue,
    startDate: val.start ?? null,
    endDate: val.end ?? null,
  })
}
</script>
