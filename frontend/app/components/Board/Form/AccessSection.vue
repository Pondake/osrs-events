<template>
  <div class="space-y-6">
    <!-- Listed toggle -->
    <u-form-field
      :label="$t('admin.board_listed')"
      :description="$t('admin.board_listed_desc')"
      name="isListed"
    >
      <u-switch
        :model-value="modelValue.isListed"
        @update:model-value="emit('update:modelValue', { ...modelValue, isListed: $event })"
      />
    </u-form-field>

    <!-- Access mode -->
    <u-form-field
      :label="$t('admin.access_mode')"
      :description="$t('admin.access_mode_desc')"
      name="accessMode"
    >
      <u-select
        :model-value="modelValue.accessMode"
        :items="accessModeOptions"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, accessMode: $event as BoardFormData['accessMode'], requiredGuildId: null })"
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
      >
        <u-select
          :model-value="modelValue.requiredGuildId ?? undefined"
          :items="guildOptions"
          :placeholder="$t('admin.required_server_placeholder')"
          class="w-full"
          @update:model-value="emit('update:modelValue', { ...modelValue, requiredGuildId: $event ?? null })"
        />
      </u-form-field>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import type { BoardFormData } from '~/components/Board/SettingsForm.vue'

defineProps<{
  modelValue: BoardFormData
}>()

const emit = defineEmits<{
  'update:modelValue': [value: BoardFormData]
}>()

const authStore = useAuthStore()

const guildOptions = computed(() =>
  (authStore.user?.guilds ?? []).map(g => ({ label: g.guildName, value: g.guildId }))
)

const accessModeOptions = [
  { label: 'Open — anyone can join', value: 'OPEN' },
  { label: 'Discord Server — members only', value: 'GUILD' },
  { label: 'Invite only', value: 'INVITE' },
]
</script>
