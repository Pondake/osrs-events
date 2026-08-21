<template>
    <Head :title="$t('auth.forgot_password_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.forgot_password_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.forgot_password_subtitle') }}</p>
                    </template>

                    <u-alert v-if="status" color="success" variant="subtle" icon="i-lucide-mail-check" :description="status" class="mb-4" />

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field :label="$t('auth.field_email')" :error="form.errors.email" required>
                            <u-input v-model="form.email" type="email" autocomplete="username" class="w-full" />
                        </u-form-field>

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('auth.cta_send_reset_link')" />
                    </form>

                    <template #footer>
                        <a :href="route('login')" class="text-sm text-primary hover:underline">{{ $t('auth.back_to_login') }}</a>
                    </template>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>
