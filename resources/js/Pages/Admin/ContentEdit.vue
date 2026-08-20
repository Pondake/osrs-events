<template>
    <Head :title="form.title" />

    <admin-layout current="content" :title="form.title || $t('cms.untitled')" :description="`/${page.slug}`">
        <template #actions>
            <div class="flex items-center gap-2">
                <span v-if="form.isDirty" class="text-xs text-muted">{{ $t('admin.site_unsaved') }}</span>
                <u-button :href="`/${page.slug}`" target="_blank" size="sm" variant="ghost" color="neutral" icon="i-lucide-external-link" :label="$t('cms.view_live')" />
                <u-button size="sm" color="primary" :loading="form.processing" :label="$t('common.save')" @click="submit" />
            </div>
        </template>

        <!-- Editor and preview side by side: the preview uses the SAME
             PageRenderer the public page does, so what it shows is what the
             page will be, not an approximation of it. That is the payoff for
             having built the renderer before the editor. -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
            <div class="space-y-6">
                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('cms.page_details') }}</span>
                    </template>

                    <div class="space-y-3">
                        <u-form-field :label="$t('cms.field_page_title')" :error="form.errors.title">
                            <u-input v-model="form.title" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('cms.field_subtitle')" :error="form.errors.subtitle">
                            <u-input v-model="form.subtitle" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('cms.field_seo_title')" :description="$t('cms.hint_seo_title')" :error="form.errors.seo_title">
                            <u-input v-model="form.seo_title" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('cms.field_seo_description')" :error="form.errors.seo_description">
                            <u-textarea v-model="form.seo_description" :rows="2" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('cms.field_published')" :description="$t('cms.hint_published')">
                            <u-switch v-model="form.is_published" :label="form.is_published ? $t('cms.published') : $t('cms.draft')" />
                        </u-form-field>
                    </div>
                </u-card>

                <u-card>
                    <template #header>
                        <span class="font-semibold">{{ $t('cms.blocks') }}</span>
                    </template>

                    <block-editor v-model="form.blocks" />
                </u-card>
            </div>

            <div class="xl:sticky xl:top-4">
                <u-card :ui="{ body: 'p-0' }">
                    <template #header>
                        <span class="font-semibold">{{ $t('cms.preview') }}</span>
                    </template>

                    <div class="p-4 max-h-[70vh] overflow-y-auto">
                        <h1 class="text-2xl font-bold text-highlighted">{{ form.title }}</h1>
                        <p v-if="form.subtitle" class="text-muted mt-1 mb-6">{{ form.subtitle }}</p>
                        <page-renderer :blocks="form.blocks" />
                    </div>
                </u-card>
            </div>
        </div>
    </admin-layout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';
import BlockEditor from '@/Components/Cms/BlockEditor.vue';
import PageRenderer from '@/Components/Cms/PageRenderer.vue';

const props = defineProps({
    page: { type: Object, required: true },
});

const form = useForm({
    title: props.page.title ?? '',
    subtitle: props.page.subtitle ?? '',
    seo_title: props.page.seoTitle ?? '',
    seo_description: props.page.seoDescription ?? '',
    is_published: props.page.isPublished,
    blocks: props.page.blocks ?? [],
});

function submit() {
    form.put(`/admin/content/${props.page.slug}`, { preserveScroll: true });
}
</script>
