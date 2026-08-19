<template>
    <Head :title="$t('auth.reset_password_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.reset_password_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.reset_password_subtitle', { email: form.email }) }}</p>
                    </template>

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field :label="$t('auth.field_email')" :error="form.errors.email" required>
                            <u-input v-model="form.email" type="email" autocomplete="username" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('profile.new_password')" :description="$t('auth.password_requirements')" :error="form.errors.password" required>
                            <u-input v-model="form.password" type="password" autocomplete="new-password" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('profile.confirm_new_password')" required>
                            <u-input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
                        </u-form-field>

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('auth.cta_reset_password')" />
                    </form>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>
