<template>
  <div class="flex flex-col gap-4">
    <!-- Team name -->
    <u-form-field :label="$t('teams.team_name')" name="name" required>
      <u-input
        :model-value="modelValue.name"
        class="w-full"
        :placeholder="$t('teams.team_name_placeholder')"
        @update:model-value="emit('update:modelValue', { ...modelValue, name: String($event) })"
      />
    </u-form-field>

    <!-- Icon: wiki search (icon-only, does NOT auto-fill name) -->
    <u-form-field
      :label="$t('teams.team_icon')"
      :description="$t('teams.team_icon_desc')"
      name="iconUrl"
    >
      <div class="flex items-start gap-3 mt-1">
        <!-- Preview -->
        <div
          class="w-16 h-16 shrink-0 flex items-center justify-center rounded-lg bg-muted border border-default overflow-hidden"
        >
          <img
            v-if="modelValue.iconUrl"
            :src="modelValue.iconUrl"
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
  </div>
</template>

<script setup lang="ts">
export interface TeamFormData {
  name: string;
  iconUrl: string;
}

interface WikiResult {
  title: string;
  iconUrl: string;
}

const props = defineProps<{ modelValue: TeamFormData }>();
const emit = defineEmits<{ 'update:modelValue': [TeamFormData] }>();

const { t } = useI18n();
const toast = useToast();

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
    toast.add({ title: t('errors.generic'), color: 'error' });
  } finally {
    searching.value = false;
  }
}

// Icon-only: does NOT auto-fill name
function applyResult(r: WikiResult) {
  emit('update:modelValue', { ...props.modelValue, iconUrl: r.iconUrl });
  wikiSearch.value = '';
  results.value = [];
}
</script>
