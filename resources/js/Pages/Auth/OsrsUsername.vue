<template>
    <Head :title="$t('auth.osrs_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.osrs_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.osrs_subtitle') }}</p>
                    </template>

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field
                            :label="$t('auth.field_osrs_username')"
                            :description="$t('auth.field_osrs_username_desc')"
                            :error="form.errors.osrs_username"
                            required
                        >
                            <u-input
                                v-model="form.osrs_username"
                                maxlength="12"
                                icon="i-lucide-user-round"
                                class="w-full"
                                autofocus
                            />
                        </u-form-field>

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('common.continue')" />
                    </form>

                    <template #footer>
                        <p class="text-xs text-muted">{{ $t('auth.osrs_change_later') }}</p>
                    </template>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    suggestion: { type: String, default: '' },
});

// Prefilled with whatever we already know them by — often the same name, and
// a wrong guess costs one edit where a right one costs nothing.
const form = useForm({ osrs_username: props.suggestion ?? '' });

function submit() {
    form.post('/welcome/osrs-username');
}
</script>
