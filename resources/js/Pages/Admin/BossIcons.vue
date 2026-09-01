<template>
    <Head :title="$t('admin.boss_icons_title')" />

    <admin-layout current="boss-icons">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-highlighted">{{ $t('admin.boss_icons_title') }}</h1>
            <p class="text-sm text-muted mt-1 max-w-3xl">{{ $t('admin.boss_icons_desc') }}</p>
        </div>

        <!-- The ones without an icon first, because they are the only reason
             to open this page. Everything else is already right and is here to
             be corrected, not to be found. -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <u-input
                v-model="search"
                icon="i-lucide-search"
                :placeholder="$t('common.search')"
                class="w-full sm:w-64"
            />
            <u-switch v-model="onlyMissing" :label="$t('admin.boss_icons_only_missing')" />
            <span class="text-sm text-muted">{{ $t('admin.boss_icons_count', { shown: visible.length, total: bosses.length }) }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <u-card
                v-for="boss in visible"
                :key="boss.metric"
                :ui="{ root: boss.url === null ? 'ring-warning/40' : '' }"
            >
                <div class="flex items-start gap-3">
                    <!-- Fixed box whether or not there is an image, so the rows
                         line up and a missing icon reads as an empty slot
                         rather than as a shorter card. -->
                    <div class="size-10 shrink-0 rounded-md bg-elevated flex items-center justify-center overflow-hidden">
                        <img v-if="boss.url" :src="boss.url" alt="" class="max-size-full object-contain">
                        <u-icon v-else name="i-lucide-image-off" class="size-4 text-muted" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-highlighted truncate">{{ label(boss.metric) }}</p>
                        <p class="text-xs text-muted">{{ sourceLabel(boss.source) }}</p>

                        <div class="flex items-center gap-2 mt-2">
                            <u-button
                                size="xs"
                                color="neutral"
                                variant="outline"
                                icon="i-lucide-pencil"
                                :label="$t('admin.boss_icons_set')"
                                @click="open(boss)"
                            />
                            <u-button
                                v-if="boss.source === 'custom'"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :label="$t('admin.boss_icons_reset')"
                                @click="reset(boss)"
                            />
                        </div>
                    </div>
                </div>
            </u-card>
        </div>

        <!-- Behind client-only for the same reason every other modal in this
             app is: u-modal reaches @nuxt/ui's #imports barrel, which is the
             SSR crash in docs/ssr-gotchas.md. -->
        <client-only>
            <u-modal v-model:open="editing" :title="editingBoss ? label(editingBoss.metric) : ''">
                <template #body>
                    <div class="space-y-4 py-2">
                        <p class="text-sm text-muted">{{ $t('admin.boss_icons_modal_desc') }}</p>

                        <!-- The same wiki picker task and team icons use. A
                             boss pet IS a wiki image, so there is no reason to
                             ask an admin to find and paste a URL by hand — and
                             every reason not to have two ways to choose one. -->
                        <u-form-field :label="$t('admin.boss_icons_from_wiki')">
                            <wiki-icon-picker v-model="iconUrl" />
                        </u-form-field>

                        <u-form-field :label="$t('admin.boss_icons_url')" :description="$t('admin.boss_icons_url_desc')" :error="error">
                            <u-input v-model="iconUrl" class="w-full" placeholder="https://oldschool.runescape.wiki/images/..." />
                        </u-form-field>
                    </div>
                </template>

                <template #footer>
                    <div class="flex justify-end gap-2 w-full">
                        <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="editing = false" />
                        <u-button color="primary" :label="$t('common.save')" :loading="saving" :disabled="!iconUrl" @click="save" />
                    </div>
                </template>
            </u-modal>
        </client-only>
    </admin-layout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AdminLayout from '@/Components/AdminLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import WikiIconPicker from '@/Components/WikiIconPicker.vue';
import { metricLabel } from '@/Support/metrics';

const props = defineProps({
    // Every boss, with the icon it would render and where that came from —
    // see BossIconService::all(). All 71, not just the ones with an icon: the
    // ones without are what somebody opens this page to fix.
    bosses: { type: Array, default: () => [] },
});

const search = ref('');
const onlyMissing = ref(false);

const editing = ref(false);
const editingBoss = ref(null);
const iconUrl = ref('');
const saving = ref(false);
const error = ref(null);

function label(metric) {
    return metricLabel(metric, 'boss');
}

function sourceLabel(source) {
    return trans(`admin.boss_icons_source_${source}`);
}

const visible = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return props.bosses.filter((boss) => {
        if (onlyMissing.value && boss.url !== null) return false;
        if (!needle) return true;

        return label(boss.metric).toLowerCase().includes(needle)
            || boss.metric.includes(needle);
    });
});

function open(boss) {
    editingBoss.value = boss;
    // Prefilled with whatever is there, so "fix this one slightly" does not
    // mean "find the URL again".
    iconUrl.value = boss.source === 'custom' ? boss.url : '';
    error.value = null;
    editing.value = true;
}

function save() {
    saving.value = true;
    error.value = null;

    router.put('/admin/boss-icons', { metric: editingBoss.value.metric, icon_url: iconUrl.value }, {
        preserveScroll: true,
        onSuccess: () => { editing.value = false; },
        onError: (errors) => { error.value = errors.icon_url ?? errors.metric ?? null; },
        onFinish: () => { saving.value = false; },
    });
}

/**
 * Drops the override. Not "clear the icon" — for 61 of these the committed
 * pet sprite comes back, and only the ten without one actually go blank.
 */
function reset(boss) {
    router.delete(`/admin/boss-icons/${boss.metric}`, { preserveScroll: true });
}
</script>
