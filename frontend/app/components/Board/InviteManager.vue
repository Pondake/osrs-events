<template>
  <div class="space-y-4">
    <!-- Existing invites -->
    <div v-if="loading" class="flex justify-center py-4">
      <u-icon name="i-lucide-loader" class="animate-spin text-muted" />
    </div>

    <div v-else-if="invites.length === 0" class="text-sm text-muted">
      {{ $t('admin.no_invites') }}
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="invite in invites"
        :key="invite.id"
        class="flex items-center gap-3 px-3 py-2 bg-muted/20 rounded-lg border border-default"
      >
        <div class="flex-1 min-w-0 space-y-0.5">
          <div class="flex items-center gap-2">
            <span class="font-mono text-sm font-semibold">{{ invite.shortCode }}</span>

            <u-badge v-if="invite.label" color="neutral" variant="subtle" size="xs">{{
              invite.label
            }}</u-badge>
          </div>

          <div class="text-xs text-muted flex flex-wrap gap-3">
            <span>
              <u-icon name="i-lucide-users" class="inline mr-0.5" />

              {{ invite.useCount
              }}<template v-if="invite.maxUses"> / {{ invite.maxUses }}</template>
            </span>

            <span v-if="invite.expiresAt">
              <u-icon name="i-lucide-clock" class="inline mr-0.5" />
              {{ formatDate(invite.expiresAt) }}
            </span>
          </div>
        </div>

        <u-button
          icon="i-lucide-copy"
          color="neutral"
          variant="ghost"
          size="xs"
          :aria-label="$t('common.copy')"
          @click="copyLink(invite)"
        />

        <u-button
          icon="i-lucide-trash"
          color="error"
          variant="ghost"
          size="xs"
          :loading="revokingId === invite.id"
          :aria-label="$t('common.delete')"
          @click="doRevoke(invite.id)"
        />
      </div>
    </div>

    <!-- Create invite form -->
    <u-separator />

    <div class="space-y-3">
      <p class="text-sm font-medium">{{ $t('admin.create_invite') }}</p>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <u-form-field :label="$t('admin.invite_label')" name="label">
          <u-input
            v-model="newLabel"
            :placeholder="$t('admin.invite_label_placeholder')"
            class="w-full"
          />
        </u-form-field>

        <u-form-field :label="$t('admin.invite_max_uses')" name="maxUses">
          <u-input
            v-model.number="newMaxUses"
            type="number"
            min="1"
            :placeholder="$t('admin.invite_unlimited')"
            class="w-full"
          />
        </u-form-field>
      </div>

      <u-button
        icon="i-lucide-plus"
        color="primary"
        variant="soft"
        size="sm"
        :loading="creating"
        :label="$t('admin.create_invite')"
        @click="doCreate"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { BoardInviteEntity } from '~/types/graphql';

import { useBoardInvites } from '~/composables/useInvites';

const props = defineProps<{
  boardId: string;
}>();

const toast = useToast();
const { t } = useI18n();
const runtimeConfig = useRuntimeConfig();

const { invites, loading, load, createInvite, revokeInvite } = useBoardInvites(props.boardId);

onMounted(() => load());

// ─── Create ───────────────────────────────────────────────────────────────────

const newLabel = ref('');
const newMaxUses = ref<number | null>(null);
const creating = ref(false);

async function doCreate() {
  creating.value = true;
  try {
    await createInvite({
      label: newLabel.value.trim() || undefined,
      maxUses: newMaxUses.value ?? undefined,
    });
    newLabel.value = '';
    newMaxUses.value = null;
    toast.add({ title: t('admin.invite_created'), color: 'success' });
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    creating.value = false;
  }
}

// ─── Revoke ───────────────────────────────────────────────────────────────────

const revokingId = ref<string | null>(null);

async function doRevoke(inviteId: string) {
  revokingId.value = inviteId;
  try {
    await revokeInvite(inviteId);
    toast.add({ title: t('admin.invite_revoked'), color: 'neutral' });
  } catch (e) {
    toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' });
  } finally {
    revokingId.value = null;
  }
}

// ─── Copy link ────────────────────────────────────────────────────────────────

function copyLink(invite: BoardInviteEntity) {
  const origin = import.meta.client ? window.location.origin : '';
  const url = `${origin}/boards/${props.boardId}/join/${invite.token}`;
  navigator.clipboard.writeText(url);
  toast.add({ title: t('admin.invite_copied'), color: 'success' });
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString();
}
</script>
