<template>
  <div class="space-y-6">
    <!-- Access mode comes first: it decides what "public" even means below. -->
    <u-form-field
      :label="$t('admin.access_mode')"
      :description="$t('admin.access_mode_desc')"
      name="accessMode"
    >
      <u-select
        :model-value="modelValue.accessMode"
        :items="accessModeOptions"
        class="w-full"
        @update:model-value="
          emit('update:modelValue', {
            ...modelValue,
            accessMode: $event as BoardFormData['accessMode'],
            requiredGuildId: null,
          })
        "
      />
    </u-form-field>

    <!-- Required Discord server (only when accessMode = GUILD) -->
    <template v-if="modelValue.accessMode === 'GUILD'">
      <u-alert
        v-if="guildOptions.length === 0"
        color="warning"
        icon="i-lucide-alert-triangle"
        :title="$t('admin.no_guilds_found')"
        :description="$t('admin.no_guilds_found_desc')"
      >
        <template #actions>
          <u-button
            size="xs"
            color="warning"
            variant="subtle"
            icon="i-lucide-log-out"
            :label="$t('admin.reauthenticate_discord')"
            @click="authStore.loginWithDiscord()"
          />
        </template>
      </u-alert>

      <u-form-field
        v-else
        :label="$t('admin.required_server')"
        :description="$t('admin.required_server_desc')"
        name="requiredGuildId"
        required
      >
        <u-select
          :model-value="modelValue.requiredGuildId ?? undefined"
          :items="guildOptions"
          :placeholder="$t('admin.required_server_placeholder')"
          class="w-full"
          @update:model-value="
            emit('update:modelValue', { ...modelValue, requiredGuildId: $event ?? null })
          "
        />
      </u-form-field>
    </template>

    <u-separator />

    <!-- Visibility. Its description changes with the access mode above, since
         "public" means something different for an invite-only board. -->
    <u-form-field
      :label="$t('admin.board_listed')"
      :description="listedDescription"
      name="isListed"
    >
      <u-switch
        :model-value="modelValue.isListed"
        @update:model-value="emit('update:modelValue', { ...modelValue, isListed: $event })"
      />
    </u-form-field>
  </div>
</template>

<script setup lang="ts">
import type { BoardFormData } from '~/components/Board/SettingsForm.vue';

import { useAuthStore } from '~/stores/auth';

const props = defineProps<{
  modelValue: BoardFormData;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData];
}>();

const authStore = useAuthStore();
const { t } = useI18n();

const guildOptions = computed(() =>
  (authStore.user?.guilds ?? []).map(g => ({ label: g.guildName, value: g.guildId })),
);

const accessModeOptions = computed(() => [
  { label: t('admin.access_mode_open'), value: 'OPEN' },
  { label: t('admin.access_mode_guild'), value: 'GUILD' },
  { label: t('admin.access_mode_invite'), value: 'INVITE' },
]);

const listedDescription = computed(() => {
  if (!props.modelValue.isListed) return t('admin.board_listed_off');
  if (props.modelValue.accessMode === 'OPEN') return t('admin.board_listed_desc_open');

  return t('admin.board_listed_desc_restricted', {
    requirement:
      props.modelValue.accessMode === 'GUILD'
        ? t('admin.board_listed_requirement_guild')
        : t('admin.board_listed_requirement_invite'),
  });
});
</script>
