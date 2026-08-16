<template>
  <div class="space-y-6">
    <!-- Listed toggle -->
    <u-form-field
      label="Listed"
      description="Show this board in the public boards list"
      name="isListed"
    >
      <u-switch
        :model-value="modelValue.isListed"
        @update:model-value="emit('update:modelValue', { ...modelValue, isListed: $event })"
      />
    </u-form-field>

    <!-- Access mode -->
    <u-form-field
      label="Access mode"
      description="Control who can join this board"
      name="accessMode"
    >
      <u-select
        :model-value="modelValue.accessMode"
        :items="accessModeOptions"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, accessMode: $event, requiredGuildId: null })"
      />
    </u-form-field>

    <!-- Required Discord server (only when accessMode = GUILD) -->
    <template v-if="modelValue.accessMode === 'GUILD'">
      <u-alert
        v-if="guildOptions.length === 0"
        color="warning"
        icon="i-lucide-alert-triangle"
        title="No Discord servers found"
        description="Your Discord server list hasn't been synced yet. Log out and log back in with Discord to load your servers. If the problem persists, contact the developer."
      >
        <template #actions>
          <u-button
            size="xs"
            color="warning"
            variant="subtle"
            icon="i-lucide-log-out"
            label="Re-authenticate with Discord"
            @click="authStore.loginWithDiscord()"
          />
        </template>
      </u-alert>

      <u-form-field
        v-else
        label="Required Discord server"
        description="Only members of this server can join"
        name="requiredGuildId"
      >
        <u-select
          :model-value="modelValue.requiredGuildId"
          :items="guildOptions"
          placeholder="Select a server…"
          class="w-full"
          @update:model-value="emit('update:modelValue', { ...modelValue, requiredGuildId: $event })"
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
