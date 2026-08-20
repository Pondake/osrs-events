<template>
    <Head :title="$t('settings.nav_admin_site')" />

    <settings-layout current="admin-site">
        <div>
            <h2 class="text-xl font-semibold text-highlighted">{{ $t('settings.nav_admin_site') }}</h2>
            <p class="text-sm text-muted mt-0.5">{{ $t('admin.site_subtitle') }}</p>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <u-card>
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_access_title') }}</span>
                </template>

                <u-form-field :label="$t('admin.site_registration')" :description="$t('admin.site_registration_desc')" :error="form.errors.registration_open">
                    <u-switch v-model="form.registration_open" :label="form.registration_open ? $t('admin.site_registration_on') : $t('admin.site_registration_off')" />
                </u-form-field>
            </u-card>

            <u-card>
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_defaults_title') }}</span>
                </template>
                <p class="text-sm text-muted mb-4">{{ $t('admin.site_defaults_desc') }}</p>

                <div class="space-y-4 max-w-sm">
                    <u-form-field :label="$t('admin.board_size')" :error="form.errors.default_board_size">
                        <u-select v-model="form.default_board_size" :items="sizeOptions" class="w-full" />
                    </u-form-field>

                    <u-form-field :label="$t('admin.dice_roll_limit')" :error="form.errors.default_dice_roll_limit">
                        <div class="flex items-center gap-3 flex-wrap">
                            <u-input
                                v-model.number="form.default_dice_roll_limit"
                                type="number"
                                min="1"
                                max="99"
                                :disabled="unlimitedRolls"
                                class="w-24"
                            />
                            <u-checkbox v-model="unlimitedRolls" :label="$t('admin.dice_roll_unlimited')" />
                        </div>
                    </u-form-field>
                </div>
            </u-card>

            <u-card>
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_announcement_title') }}</span>
                </template>

                <u-form-field :description="$t('admin.site_announcement_desc')" :error="form.errors.announcement">
                    <u-textarea v-model="form.announcement" :rows="2" :maxlength="280" class="w-full" :placeholder="$t('admin.site_announcement_placeholder')" />
                </u-form-field>

                <p class="text-xs text-muted mt-1">{{ (form.announcement ?? '').length }} / 280</p>

                <u-form-field :label="$t('admin.site_announcement_type')" :error="form.errors.announcement_type" class="mt-4 max-w-xs">
                    <!-- The icon slots make the dropdown show what each type
                         looks like, rather than four indistinguishable words. -->
                    <u-select v-model="form.announcement_type" :items="typeOptions" class="w-full">
                        <template #leading>
                            <u-icon :name="selectedStyle.icon" class="size-4" :class="`text-${selectedStyle.color}`" />
                        </template>
                        <template #item-leading="{ item }">
                            <u-icon :name="item.icon" class="size-4" />
                        </template>
                    </u-select>
                </u-form-field>

                <!-- Shows exactly what a visitor would see, since a banner
                     that's live site-wide is worth previewing before saving.
                     Styling comes from the same map the real banner uses
                     (Support/announcement.js) so the two can't disagree. -->
                <div v-if="form.announcement" class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-2">{{ $t('admin.site_announcement_preview') }}</p>
                    <u-alert :color="selectedStyle.color" variant="subtle" :icon="selectedStyle.icon" :description="form.announcement" />
                </div>
            </u-card>

            <u-button type="submit" color="primary" :loading="form.processing" :label="$t('common.save')" />
        </form>
    </settings-layout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';
import { announcementTypeOptions, styleFor } from '@/Support/announcement';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    registration_open: props.settings.registration_open,
    default_board_size: props.settings.default_board_size,
    default_dice_roll_limit: props.settings.default_dice_roll_limit,
    announcement: props.settings.announcement ?? '',
    announcement_type: props.settings.announcement_type ?? 'info',
});

const typeOptions = computed(() => announcementTypeOptions());
const selectedStyle = computed(() => styleFor(form.announcement_type));

// null means unlimited (same convention as boards.dice_roll_limit), so the
// checkbox is a view over the value rather than a field of its own.
const unlimitedRolls = ref(props.settings.default_dice_roll_limit === null);

watch(unlimitedRolls, (unlimited) => {
    form.default_dice_roll_limit = unlimited ? null : 1;
});

const sizeOptions = computed(() =>
    ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
        label: trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size], tiles: BOARD_TILE_COUNT[size] }),
        value: size,
    })),
);

function submit() {
    form.put('/settings/admin/site', { preserveScroll: true });
}
</script>
