<template>
  <nuxt-layout>
    <u-container class="my-12">
      <div class="max-w-sm mx-auto text-center space-y-4">
        <template v-if="joining">
          <u-icon name="i-lucide-loader" class="text-4xl text-muted animate-spin block mx-auto" />
          <p class="text-muted">{{ $t('board.joining') }}</p>
        </template>

        <template v-else-if="joinError">
          <u-icon name="i-lucide-alert-circle" class="text-4xl text-error block mx-auto" />
          <h2 class="text-lg font-semibold">{{ $t('board.join_failed') }}</h2>
          <p class="text-sm text-muted">{{ joinError }}</p>
          <u-button to="/boards" color="neutral" variant="outline" :label="$t('nav.boards')" />
        </template>
      </div>
    </u-container>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { joinBoard } from '~/composables/useAccess'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const boardId = route.params.id as string
const token = route.params.token as string

const joining = ref(true)
const joinError = ref<string | null>(null)

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    if (import.meta.client) {
      localStorage.setItem('post_auth_redirect', route.fullPath)
    }
    authStore.loginWithDiscord()
    return
  }

  try {
    await joinBoard(boardId, token)
    await router.replace(`/boards/${boardId}`)
    useToast().add({ title: t('board.joined_board'), color: 'success' })
  } catch (e) {
    joining.value = false
    joinError.value = (e as Error).message
  }
})
</script>
