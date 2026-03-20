<template>
  <nuxt-layout :title="$t('admin.create_board')">

    <u-page-body>
      <u-container class="max-w-2xl">
        <u-card class="osrs-border">
          <u-form :state="form" :schema="schema" @submit="onSubmit">
            <div class="space-y-6">
              <!-- Board title -->
              <u-form-field
                :label="$t('admin.board_title')"
                :description="$t('admin.board_title_desc')"
                name="title"
                required
              >
                <u-input
                  v-model="form.title"
                  :placeholder="$t('admin.board_title_placeholder')"
                  class="w-full"
                />
              </u-form-field>

              <!-- Board description -->
              <u-form-field
                :label="$t('admin.board_description')"
                :description="$t('admin.board_description_desc')"
                name="description"
              >
                <u-textarea
                  v-model="form.description"
                  :placeholder="$t('admin.board_description_placeholder')"
                  class="w-full"
                  :rows="3"
                />
              </u-form-field>

              <!-- Board size -->
              <u-form-field
                :label="$t('admin.board_size')"
                :description="$t('admin.board_size_desc')"
                name="size"
                required
              >
                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
              </u-form-field>

              <!-- Date range picker -->
              <u-form-field
                :label="$t('admin.date_range')"
                :description="$t('admin.date_range_desc')"
                name="dateRange"
              >
                <u-input-date v-model="dateRange" range class="w-full" />
              </u-form-field>

              <!-- Dice roll limit -->
              <u-form-field
                :label="$t('admin.dice_roll_limit')"
                :description="$t('admin.dice_roll_limit_desc')"
                name="diceRollLimit"
              >
                <div class="flex items-center gap-3">
                  <u-input
                    v-model.number="form.diceRollLimit"
                    type="number"
                    min="1"
                    max="99"
                    :disabled="form.unlimitedRolls"
                    class="w-32"
                  />

                  <u-checkbox
                    v-model="form.unlimitedRolls"
                    :label="$t('admin.dice_roll_unlimited')"
                  />
                </div>
              </u-form-field>

              <!-- Editors -->
              <u-form-field
                :label="$t('admin.editors')"
                :description="$t('admin.editors_desc')"
                name="editors"
              >
                <div class="space-y-2">
                  <div class="flex gap-2">
                    <u-input
                      v-model="authorSearch"
                      :placeholder="$t('common.search')"
                      class="flex-1"
                      @input="searchUsers"
                    />
                  </div>

                  <!-- Search results -->
                  <div
                    v-if="userResults.length > 0"
                    class="border border-muted rounded-lg overflow-hidden"
                  >
                    <button
                      v-for="user in userResults"
                      :key="user.id"
                      type="button"
                      class="w-full flex items-center gap-3 px-3 py-2 hover:bg-muted transition-colors text-left"
                      @click="addAuthor(user)"
                    >
                      <u-avatar
                        :src="user.avatarUrl ?? undefined"
                        :alt="user.discordUsername"
                        size="xs"
                      />

                      <span>{{ user.discordUsername }}</span>
                    </button>
                  </div>

                  <!-- Selected authors -->
                  <div v-if="selectedAuthors.length > 0" class="flex flex-wrap gap-2 mt-2">
                    <u-badge
                      v-for="author in selectedAuthors"
                      :key="author.id"
                      color="primary"
                      variant="subtle"
                      class="flex items-center gap-1"
                    >
                      {{ author.discordUsername }}
                      <button
                        type="button"
                        class="ml-1 hover:text-red-400"
                        @click="removeAuthor(author.id)"
                      >
                        <u-icon name="i-heroicons-x-mark" class="w-3 h-3" />
                      </button>
                    </u-badge>
                  </div>
                </div>
              </u-form-field>

              <!-- Form actions -->
              <div class="flex justify-end gap-3 pt-4">
                <u-button to="/admin/boards" color="neutral" variant="ghost">
                  {{ $t('common.cancel') }}
                </u-button>

                <u-button
                  type="submit"
                  color="primary"
                  :loading="submitting"
                  icon="i-heroicons-check"
                >
                  {{ $t('common.create') }}
                </u-button>
              </div>
            </div>
          </u-form>
        </u-card>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
import { today, getLocalTimeZone } from '@internationalized/date';
import * as z from 'zod';

import type { CalendarDate } from '@internationalized/date';
import type { FormSubmitEvent } from '@nuxt/ui';

definePageMeta({ middleware: ['admin'] });

const { t } = useI18n();
const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();

const CREATE_BOARD_MUTATION = `
  mutation CreateBoard($input: CreateBoardInput!) {
    createBoard(input: $input) {
      id
      title
    }
  }
`;

const SEARCH_USERS_QUERY = `
  query SearchUsers($search: String!) {
    users(search: $search) {
      id
      discordUsername
      discordId
      avatarUrl
    }
  }
`;

const schema = z.object({
  title: z.string().min(1, t('validation.title_required')).max(100, t('validation.title_too_long')),
  description: z.string().max(500, t('validation.desc_too_long')).optional().or(z.literal('')),
  size: z.enum(['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9']),
});
type Schema = z.output<typeof schema>;

const sizeOptions = [
  { label: '5×5 (25 tiles)', value: 'SIZE_5X5' },
  { label: '7×7 (49 tiles)', value: 'SIZE_7X7' },
  { label: '9×9 (81 tiles)', value: 'SIZE_9X9' },
];

const form = reactive({
  title: '',
  description: '',
  size: 'SIZE_7X7' as 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9',
  diceRollLimit: 3,
  unlimitedRolls: false,
});

const todayDate = today(getLocalTimeZone());
const dateRange = shallowRef<{ start: CalendarDate | null; end: CalendarDate | null }>({
  start: todayDate,
  end: todayDate.add({ months: 1 }),
});

const authorSearch = ref('');
const userResults = ref<any[]>([]);
const selectedAuthors = ref<any[]>([]);
const submitting = ref(false);

// Pre-add the current user as an author
onMounted(() => {
  if (authStore.user) {
    selectedAuthors.value = [authStore.user];
  }
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function searchUsers() {
  if (searchTimeout) clearTimeout(searchTimeout);
  if (authorSearch.value.length < 2) {
    userResults.value = [];
    return;
  }
  searchTimeout = setTimeout(async () => {
    try {
      const result = await useGqlMutation<{ users: any[] }>(SEARCH_USERS_QUERY, {
        search: authorSearch.value,
      });
      userResults.value = result.users.filter(
        (u: any) => !selectedAuthors.value.find(a => a.id === u.id),
      );
    } catch {
      userResults.value = [];
    }
  }, 300);
}

function addAuthor(user: any) {
  if (!selectedAuthors.value.find(a => a.id === user.id)) {
    selectedAuthors.value.push(user);
  }
  userResults.value = [];
  authorSearch.value = '';
}

function removeAuthor(userId: string) {
  // Don't allow removing yourself
  if (userId === authStore.user?.id) return;
  selectedAuthors.value = selectedAuthors.value.filter(a => a.id !== userId);
}

async function onSubmit(_event: FormSubmitEvent<Schema>) {
  submitting.value = true;
  try {
    const toISO = (d: CalendarDate | null) => (d ? `${d.toString()}T00:00:00.000Z` : null);

    const input = {
      title: form.title.trim(),
      description: form.description.trim() || null,
      size: form.size,
      startDate: toISO(dateRange.value.start),
      endDate: toISO(dateRange.value.end),
      diceRollLimit: form.unlimitedRolls ? null : form.diceRollLimit,
      authorIds: selectedAuthors.value.map(a => a.id),
    };
    const result = await useGqlMutation<{ createBoard: { id: string } }>(CREATE_BOARD_MUTATION, {
      input,
    });
    toast.add({ title: t('admin.board_created'), color: 'success' });
    router.push(`/boards/${result.createBoard.id}`);
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error' });
  } finally {
    submitting.value = false;
  }
}
</script>
