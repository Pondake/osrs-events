<template>
  <div class="flex flex-col gap-4">
    <!-- Icon: wiki search + preview -->
    <u-form-field
      :label="$t('admin.task_icon_label')"
      :description="$t('admin.task_icon_wiki_desc')"
      name="iconUrl"
    >
      <div class="flex items-start gap-3 mt-1">
        <!-- Preview -->
        <div
          class="w-16 h-16 shrink-0 flex items-center justify-center rounded-lg bg-muted border border-default overflow-hidden"
        >
          <img
            v-if="iconUrl"
            :src="iconUrl"
            alt="icon preview"
            class="w-14 h-14 object-contain image-rendering-pixelated"
          />

          <u-icon v-else name="i-heroicons-photo" class="text-3xl text-muted" />
        </div>

        <!-- Wiki search input + dropdown -->
        <div class="flex-1 relative">
          <u-input
            v-model="wikiSearch"
            :placeholder="$t('admin.search_wiki')"
            :loading="searching"
            icon="i-heroicons-magnifying-glass"
            class="w-full"
          />

          <div
            v-if="results.length > 0"
            class="absolute z-20 w-full mt-1 border border-default rounded-lg bg-[var(--ui-bg)] shadow-lg max-h-52 overflow-y-auto"
          >
            <button
              v-for="r in results"
              :key="r.title"
              type="button"
              class="w-full flex items-center gap-3 px-3 py-2 hover:bg-muted transition-colors text-left"
              @click="applyResult(r)"
            >
              <img
                v-if="r.iconUrl"
                :src="r.iconUrl"
                :alt="r.title"
                class="w-8 h-8 object-contain image-rendering-pixelated"
              />

              <span class="text-sm">{{ r.title }}</span>
            </button>
          </div>
        </div>
      </div>
    </u-form-field>

    <!-- Title -->
    <u-form-field :label="$t('admin.task_title_label')" name="title" required>
      <u-input
        :model-value="modelValue.title"
        class="w-full"
        @update:model-value="emit('update:modelValue', { ...modelValue, title: String($event) })"
      />
    </u-form-field>

    <!-- Description -->
    <u-form-field :label="$t('admin.task_desc_label')" name="description">
      <u-textarea
        :model-value="modelValue.description"
        class="w-full"
        :rows="3"
        @update:model-value="
          emit('update:modelValue', { ...modelValue, description: String($event) })
        "
      />
    </u-form-field>
  </div>
</template>

<script setup lang="ts">
export interface TaskFormData {
  title: string;
  iconUrl: string;
  description: string;
}

interface WikiResult {
  title: string;
  iconUrl: string;
}

const props = defineProps<{ modelValue: TaskFormData }>();
const emit = defineEmits<{ 'update:modelValue': [TaskFormData] }>();

const { t } = useI18n();
const toast = useToast();

// Sync icon preview with modelValue
const iconUrl = computed(() => props.modelValue.iconUrl);

// Wiki search state
const wikiSearch = ref('');
const results = ref<WikiResult[]>([]);
const searching = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(wikiSearch, val => {
  if (debounceTimer) clearTimeout(debounceTimer);
  results.value = [];
  if (!val.trim() || val.trim().length < 2) return;
  debounceTimer = setTimeout(searchWiki, 400);
});

async function searchWiki() {
  if (!wikiSearch.value.trim()) return;
  searching.value = true;
  try {
    const query = encodeURIComponent(wikiSearch.value.trim());
    const url = `https://oldschool.runescape.wiki/api.php?action=query&generator=search&gsrsearch=${query}&gsrlimit=15&prop=pageimages&piprop=thumbnail&pithumbsize=64&format=json&origin=*`;
    const res = await $fetch<any>(url);
    const pages = Object.values(res?.query?.pages ?? {}) as any[];
    results.value = pages
      .filter((p: any) => p.thumbnail?.source)
      .sort((a: any, b: any) => (a.index ?? 0) - (b.index ?? 0))
      .slice(0, 10)
      .map((p: any) => ({ title: p.title, iconUrl: p.thumbnail.source }));
  } catch {
    toast.add({ title: t('errors.generic'), color: 'error', id: 'validation' });
  } finally {
    searching.value = false;
  }
}

function applyResult(r: WikiResult) {
  emit('update:modelValue', { ...props.modelValue, title: r.title, iconUrl: r.iconUrl });
  wikiSearch.value = '';
  results.value = [];
}
</script>
