<template>
  <div class="space-y-6">
    <!-- Title -->
    <u-form-field
      :label="$t('admin.board_title')"
      :description="$t('admin.board_title_desc')"
      name="title"
      required
    >
      <u-input
        :model-value="modelValue.title"
        :placeholder="$t('admin.board_title_placeholder')"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, title: $event })"
      />
    </u-form-field>

    <!-- Description -->
    <u-form-field
      :label="$t('admin.board_description')"
      :description="$t('admin.board_description_desc')"
      name="description"
    >
      <u-textarea
        :model-value="modelValue.description"
        :placeholder="$t('admin.board_description_placeholder')"
        class="w-full"
        :rows="3"
        @update:model-value="emit('update:modelValue', { ...modelValue, description: $event })"
      />
    </u-form-field>

    <!-- Board size -->
    <u-form-field
      :label="$t('admin.board_size')"
      :description="$t('admin.board_size_desc')"
      name="size"
      required
    >
      <u-select
        :model-value="modelValue.size"
        :items="sizeOptions"
        class="w-full"
        @update:model-value="
          emit('update:modelValue', { ...modelValue, size: $event as BoardFormData['size'] })
        "
      />
    </u-form-field>

    <!-- Board mode -->
    <u-form-field
      :label="$t('admin.board_mode')"
      :description="$t('admin.board_mode_desc')"
      name="mode"
    >
      <u-select
        :model-value="modelValue.mode"
        :items="modeOptions"
        class="w-full"
        @update:model-value="
          emit('update:modelValue', { ...modelValue, mode: $event as BoardFormData['mode'] })
        "
      />
    </u-form-field>
  </div>
</template>

<script setup lang="ts">
import type { BoardFormData } from '~/components/Board/SettingsForm.vue';

import { BOARD_SIZES } from '~/schemas/board';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '~/utils/board';

defineProps<{
  modelValue: BoardFormData;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData];
}>();

const { t } = useI18n();

const sizeOptions = computed(() =>
  BOARD_SIZES.map(size => ({
    label: t('admin.board_size_option', {
      size: BOARD_SIZE_LABEL[size],
      tiles: BOARD_TILE_COUNT[size],
    }),
    value: size,
  })),
);

const modeOptions = computed(() => [
  { label: t('admin.board_mode_solo'), value: 'SOLO' },
  { label: t('admin.board_mode_team'), value: 'TEAM' },
]);
</script>
