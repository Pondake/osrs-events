<template>
  <nuxt-layout :title="$t('profile.title')">
    <u-page-body>
      <u-container class="max-w-3xl">
        <div v-if="!authStore.user" class="text-center py-12 text-muted">
          <u-skeleton class="h-24 w-24 rounded-full mx-auto mb-4" />
          <u-skeleton class="h-6 w-48 mx-auto" />
        </div>

        <div v-else class="space-y-8">
          <!-- Profile header card -->
          <u-card class="osrs-border">
            <div class="flex items-center gap-6">
              <u-avatar
                :src="authStore.avatarUrl ?? undefined"
                :alt="authStore.user.discordUsername"
                size="3xl"
                class="ring-4 ring-primary-500/50"
              />

              <div class="flex-1 min-w-0">
                <!-- Editable display name -->
                <div v-if="!editingProfile" class="flex items-center gap-2">
                  <h2 class="text-2xl font-bold osrs-font">{{ authStore.displayName }}</h2>
                  <u-button
                    size="xs"
                    color="neutral"
                    variant="ghost"
                    icon="i-lucide-pencil"
                    @click="startEditingProfile"
                  />
                </div>

                <div v-else class="flex items-center gap-2 flex-wrap">
                  <u-input
                    v-model="nicknameInput"
                    :placeholder="authStore.user.discordUsername"
                    class="flex-1"
                    size="sm"
                  />
                  <u-button
                    size="sm"
                    color="primary"
                    :loading="savingProfile"
                    icon="i-lucide-check"
                    @click="saveProfile"
                  />
                  <u-button
                    size="sm"
                    color="neutral"
                    variant="ghost"
                    icon="i-lucide-x"
                    @click="cancelEditingProfile"
                  />
                </div>

                <p v-if="authStore.user.nickname" class="text-xs text-muted mt-0.5">
                  Discord: {{ authStore.user.discordUsername }}
                </p>

                <p class="text-muted text-sm mt-1">
                  <u-icon name="i-lucide-id-card" class="inline mr-1" />
                  Discord ID: {{ authStore.user.discordId }}
                </p>

                <div class="flex flex-wrap gap-2 mt-3">
                  <u-badge
                    v-for="role in authStore.user.roles"
                    :key="role"
                    :color="roleColor(role)"
                    variant="subtle"
                    class="capitalize"
                  >
                    <u-icon :name="roleIcon(role)" class="mr-1" />
                    {{ role }}
                  </u-badge>
                </div>
              </div>
            </div>
          </u-card>

          <!-- Player boards -->
          <div>
            <h3 class="text-lg font-semibold osrs-font mb-4">{{ $t('profile.your_boards') }}</h3>

            <div v-if="loading" class="space-y-3">
              <u-skeleton v-for="i in 3" :key="i" class="h-20" />
            </div>

            <div v-else-if="playerBoards.length === 0" class="text-center py-8 text-muted">
              <u-icon name="i-lucide-layout-grid" class="text-5xl mb-4 block mx-auto" />
              <p>{{ $t('profile.no_boards') }}</p>
            </div>

            <div v-else class="space-y-3">
              <u-card v-for="pb in playerBoards" :key="pb.id" class="osrs-border">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1 flex-wrap">
                      <nuxt-link
                        :to="`/boards/${pb.board.id}`"
                        class="text-lg font-semibold osrs-font hover:text-primary-500 transition-colors truncate"
                      >
                        {{ pb.board.title }}
                      </nuxt-link>
                      <u-badge color="primary" variant="subtle">
                        {{ formatBoardSize(pb.board.size) }}
                      </u-badge>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-muted flex-wrap">
                      <span>
                        <u-icon name="i-lucide-map-pin" class="inline mr-1" />
                        {{ $t('board.position', { pos: pb.currentPosition }) }}
                      </span>
                      <span>
                        <u-icon name="i-lucide-circle-check" class="inline mr-1" />
                        {{ pb.completedTiles.length }} {{ $t('profile.tiles_completed') }}
                      </span>
                    </div>
                  </div>

                  <!-- Progress bar -->
                  <div class="w-32 shrink-0">
                    <div class="flex justify-between text-xs text-muted mb-1">
                      <span>{{ $t('profile.progress') }}</span>
                      <span>{{ progressPct(pb) }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-muted overflow-hidden">
                      <div
                        class="h-full bg-primary-500 rounded-full transition-all"
                        :style="{ width: `${progressPct(pb)}%` }"
                      />
                    </div>
                  </div>

                  <u-button
                    :to="`/boards/${pb.board.id}`"
                    icon="i-lucide-play"
                    color="primary"
                    variant="outline"
                    size="sm"
                    :label="$t('boards.play')"
                  />
                </div>
              </u-card>
            </div>
          </div>
        </div>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import type { PlayerBoardEntity } from '~/types/graphql'
import { useMyPlayerBoards } from '~/composables/usePlayers'
import { useMe } from '~/composables/useUsers'
import { formatBoardSize, BOARD_TILE_COUNT } from '~/utils/board'

definePageMeta({ middleware: ['auth'] })

const authStore = useAuthStore()
const { t } = useI18n()
const toast = useToast()

// ─── Profile editing ──────────────────────────────────────────────────────────

const editingProfile = ref(false)
const nicknameInput = ref('')
const savingProfile = ref(false)

const { updateProfile } = await useMe()

function startEditingProfile() {
  nicknameInput.value = authStore.user?.nickname ?? ''
  editingProfile.value = true
}

function cancelEditingProfile() {
  editingProfile.value = false
}

async function saveProfile() {
  savingProfile.value = true
  try {
    const updated = await updateProfile(nicknameInput.value.trim() || null)
    if (authStore.user) {
      authStore.user.nickname = updated.nickname
    }
    editingProfile.value = false
    toast.add({ title: t('profile.nickname_saved'), color: 'success' })
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
  } finally {
    savingProfile.value = false
  }
}

// ─── Player boards ────────────────────────────────────────────────────────────

const { playerBoards, loading, load: loadBoards } = useMyPlayerBoards()

onMounted(loadBoards)

// ─── Role display helpers ─────────────────────────────────────────────────────

function roleColor(name: string): string {
  const map: Record<string, string> = {
    ADMIN: 'error',
    EDITOR: 'warning',
    TEAM_MANAGER: 'info',
    PLAYER: 'primary',
  }
  return map[name] ?? 'neutral'
}

function roleIcon(name: string): string {
  const map: Record<string, string> = {
    ADMIN: 'i-lucide-shield-check',
    EDITOR: 'i-lucide-pencil',
    TEAM_MANAGER: 'i-lucide-users',
    PLAYER: 'i-lucide-user',
  }
  return map[name] ?? 'i-lucide-user'
}

// ─── Progress helpers ─────────────────────────────────────────────────────────

function progressPct(pb: PlayerBoardEntity): number {
  const total = BOARD_TILE_COUNT[pb.board.size] ?? 25
  if (total <= 1) return 0
  const pos = Math.max(0, pb.currentPosition)
  return Math.min(99, Math.floor((pos / (total - 1)) * 100))
}
</script>
