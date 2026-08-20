<template>
    <Head :title="$t('settings.nav_admin_site')" />

    <settings-layout current="admin-site">
        <div>
            <h2 class="text-xl font-semibold text-highlighted">{{ $t('settings.nav_admin_site') }}</h2>
            <p class="text-sm text-muted mt-0.5">{{ $t('admin.site_subtitle') }}</p>
        </div>

        <!-- Second-level nav, horizontal on every breakpoint: the settings
             shell already spends a 208px rail on md+, and a second rail
             inside it would leave the forms under ~440px. Plain buttons for
             the same reason SettingsLayout uses plain <a>s — u-tabs reaches
             the '#imports' specifier and would drag this whole page behind
             ClientOnly. Scrolls sideways on mobile with shrink-0 children,
             matching the shell's own overflow handling. -->
        <nav class="flex gap-1 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 border-b border-default">
            <button
                v-for="section in sections"
                :key="section.key"
                type="button"
                class="flex items-center gap-2 px-3 py-2 rounded-t-md text-sm shrink-0 whitespace-nowrap border-b-2 -mb-px transition-colors"
                :class="active === section.key
                    ? 'border-primary text-highlighted font-medium'
                    : 'border-transparent text-muted hover:text-highlighted'"
                @click="select(section.key)"
            >
                <u-icon :name="section.icon" class="size-4 shrink-0" />
                {{ section.label }}
            </button>
        </nav>

        <!-- One form across every section, so switching sections never
             discards a pending edit and Save commits all of them at once.
             v-show rather than v-if to keep caret and scroll position in a
             section the user tabs away from and back to. -->
        <form class="space-y-8" @submit.prevent="submit">
            <u-card v-show="active === 'access'">
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_access_title') }}</span>
                </template>

                <u-form-field :label="$t('admin.site_registration')" :description="$t('admin.site_registration_desc')" :error="form.errors.registration_open">
                    <u-switch v-model="form.registration_open" :label="form.registration_open ? $t('admin.site_registration_on') : $t('admin.site_registration_off')" />
                </u-form-field>
            </u-card>

            <u-card v-show="active === 'boards'">
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

            <u-card v-show="active === 'support'">
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_support_title') }}</span>
                </template>

                <u-form-field
                    :label="$t('admin.site_kofi_url')"
                    :description="$t('admin.site_kofi_url_desc')"
                    :error="form.errors.kofi_url"
                    class="max-w-lg"
                >
                    <u-input v-model="form.kofi_url" type="url" class="w-full" placeholder="https://ko-fi.com/yourname" />
                </u-form-field>
            </u-card>

            <u-card v-show="active === 'announcement'">
                <template #header>
                    <span class="font-semibold">{{ $t('admin.site_announcement_title') }}</span>
                </template>

                <u-form-field :description="$t('admin.site_announcement_desc')" :error="form.errors.announcement">
                    <u-textarea ref="announcementInput" v-model="form.announcement" :rows="2" :maxlength="280" class="w-full" :placeholder="$t('admin.site_announcement_placeholder')" />
                </u-form-field>

                <div class="flex items-center justify-between gap-3 mt-1 flex-wrap">
                    <div class="flex items-center gap-2">
                        <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-link" :label="$t('admin.site_announcement_insert_link')" @click="insertLink" />
                        <span class="text-xs text-muted">{{ $t('admin.site_announcement_syntax') }}</span>
                    </div>
                    <p class="text-xs text-muted">{{ (form.announcement ?? '').length }} / 280</p>
                </div>

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
                     Styling comes from the same map the real banner uses and
                     the copy runs through the same parser, so the markdown
                     is rendered here rather than shown raw. -->
                <div v-if="form.announcement" class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-2">{{ $t('admin.site_announcement_preview') }}</p>
                    <u-alert :color="selectedStyle.color" variant="subtle" :icon="selectedStyle.icon">
                        <template #description>
                            <rich-text :text="form.announcement" />
                        </template>
                    </u-alert>
                </div>
            </u-card>

            <div class="flex items-center gap-3">
                <u-button type="submit" color="primary" :loading="form.processing" :label="$t('common.save')" />
                <!-- Sections hide each other, so without this an edit made
                     two sections back is invisible at the moment of saving. -->
                <span v-if="form.isDirty" class="text-sm text-muted">{{ $t('admin.site_unsaved') }}</span>
            </div>
        </form>
    </settings-layout>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import RichText from '@/Components/RichText.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';
import { announcementTypeOptions, styleFor } from '@/Support/announcement';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    registration_open: props.settings.registration_open,
    default_board_size: props.settings.default_board_size,
    default_dice_roll_limit: props.settings.default_dice_roll_limit,
    kofi_url: props.settings.kofi_url ?? '',
    announcement: props.settings.announcement ?? '',
    announcement_type: props.settings.announcement_type ?? 'info',
});

const sections = computed(() => [
    { key: 'access', icon: 'i-lucide-door-open', label: trans('admin.site_section_access') },
    { key: 'boards', icon: 'i-lucide-layout-grid', label: trans('admin.site_section_boards') },
    { key: 'support', icon: 'i-lucide-coffee', label: trans('admin.site_section_support') },
    { key: 'announcement', icon: 'i-lucide-megaphone', label: trans('admin.site_section_announcement') },
]);

const active = ref('access');

// Hash rather than a query param or per-section route: it makes a section
// linkable and reload-safe without a controller action per section. Read in
// onMounted because `location` doesn't exist during SSR.
onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (sections.value.some((section) => section.key === hash)) {
        active.value = hash;
    }
});

function select(key) {
    active.value = key;
    // replaceState, not pushState: back should leave settings, not step
    // through every section the user happened to click.
    window.history.replaceState(null, '', `#${key}`);
}

const typeOptions = computed(() => announcementTypeOptions());
const selectedStyle = computed(() => styleFor(form.announcement_type));

const announcementInput = ref(null);

/**
 * Wraps the selection in link syntax and drops the caret where the URL
 * goes. A full WYSIWYG editor would be a lot of weight for 280 characters
 * of banner copy — this plus the live preview covers the same ground.
 */
function insertLink() {
    const el = announcementInput.value?.textareaRef;
    const value = form.announcement ?? '';
    const start = el?.selectionStart ?? value.length;
    const end = el?.selectionEnd ?? value.length;
    const label = value.slice(start, end) || trans('admin.site_announcement_link_label');

    // Caret lands at prefix.length — computed from the built string rather
    // than by counting syntax characters by hand.
    const prefix = `${value.slice(0, start)}[${label}](https://`;
    form.announcement = `${prefix})${value.slice(end)}`;

    nextTick(() => {
        el?.focus();
        el?.setSelectionRange(prefix.length, prefix.length);
    });
}

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
