<template>
  <u-container class="my-12">
    <div class="max-w-md mx-auto text-center space-y-4">
      <u-icon :name="gateIcon" class="text-5xl text-muted block mx-auto" />
      <h2 class="text-xl font-semibold osrs-font">{{ gateTitle }}</h2>
      <p class="text-muted text-sm">{{ gateDescription }}</p>

      <!-- OPEN / GUILD (user has guild) -->
      <u-button
        v-if="canJoinDirectly"
        color="primary"
        icon="i-lucide-log-in"
        :loading="joining"
        :label="$t('board.join_board')"
        @click="$emit('join')"
      />

      <!-- INVITE — short-code input -->
      <div v-else-if="accessMode === 'INVITE'" class="flex gap-2 justify-center">
        <u-input
          v-model="codeInput"
          :placeholder="$t('board.enter_code')"
          class="w-40 font-mono uppercase"
          maxlength="6"
          @keyup.enter="submitCode"
        />
        <u-button
          color="primary"
          :loading="joining"
          :label="$t('board.join_with_code')"
          @click="submitCode"
        />
      </div>

      <!-- GUILD — user missing guild -->
      <div v-else-if="accessMode === 'GUILD' && !userHasGuild" class="space-y-2">
        <p class="text-sm text-warning">
          {{ $t('board.requires_guild', { guild: requiredGuildName ?? $t('board.unknown_guild') }) }}
        </p>
        <u-button
          color="neutral"
          variant="outline"
          size="sm"
          icon="i-lucide-refresh-cw"
          :label="$t('board.relink_discord')"
          @click="relinkDiscord"
        />
      </div>
    </div>
  </u-container>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  accessMode: 'OPEN' | 'GUILD' | 'INVITE'
  requiredGuildId: string | null
  joining: boolean
}>()

const emit = defineEmits<{
  join: []
  'join-with-code': [code: string]
}>()

const authStore = useAuthStore()

const userHasGuild = computed(() =>
  props.accessMode === 'GUILD' && props.requiredGuildId
    ? (authStore.user?.guilds ?? []).some(g => g.guildId === props.requiredGuildId)
    : false
)

const requiredGuildName = computed(() =>
  (authStore.user?.guilds ?? []).find(g => g.guildId === props.requiredGuildId)?.guildName ?? null
)

const canJoinDirectly = computed(() =>
  props.accessMode === 'OPEN' || (props.accessMode === 'GUILD' && userHasGuild.value)
)

const gateIcon = computed(() => {
  if (props.accessMode === 'INVITE') return 'i-lucide-lock'
  if (props.accessMode === 'GUILD') return 'i-lucide-shield'
  return 'i-lucide-log-in'
})

const { t } = useI18n()

const gateTitle = computed(() => {
  if (props.accessMode === 'INVITE') return t('board.invite_only')
  if (props.accessMode === 'GUILD') return t('board.guild_only')
  return t('board.join_to_play')
})

const gateDescription = computed(() => {
  if (props.accessMode === 'INVITE') return t('board.invite_only_desc')
  if (props.accessMode === 'GUILD' && !userHasGuild.value) return t('board.guild_only_blocked_desc')
  if (props.accessMode === 'GUILD') return t('board.guild_only_desc', { guild: requiredGuildName.value ?? '' })
  return t('board.join_to_play_desc')
})

const codeInput = ref('')

function submitCode() {
  const code = codeInput.value.trim().toUpperCase()
  if (!code) return
  emit('join-with-code', code)
}

function relinkDiscord() {
  authStore.loginWithDiscord()
}
</script>
