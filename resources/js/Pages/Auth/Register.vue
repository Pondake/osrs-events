<template>
    <Head :title="$t('auth.register_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.register_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.register_subtitle') }}</p>
                    </template>

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field :label="$t('auth.field_display_name')" :description="$t('auth.field_display_name_desc')" :error="form.errors.nickname" required>
                            <u-input v-model="form.nickname" autocomplete="nickname" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('auth.field_email')" :error="form.errors.email" required>
                            <u-input v-model="form.email" type="email" autocomplete="username" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('auth.field_password')" :description="$t('auth.password_requirements')" :error="form.errors.password" required>
                            <u-input v-model="form.password" type="password" autocomplete="new-password" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('auth.field_password_confirmation')" required>
                            <u-input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
                        </u-form-field>

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('auth.cta_register')" />
                    </form>

                    <template #footer>
                        <div class="flex items-center gap-3 text-xs text-muted uppercase tracking-wide">
                            <div class="h-px flex-1 bg-default" />
                            {{ $t('auth.or') }}
                            <div class="h-px flex-1 bg-default" />
                        </div>
                        <u-button :href="route('login')" color="neutral" variant="outline" block icon="i-simple-icons-discord" class="mt-4" :label="$t('auth.continue_with_discord')" />
                        <p class="text-center text-sm text-muted mt-4">
                            {{ $t('auth.have_account') }}
                            <a :href="route('auth.login')" class="text-primary hover:underline">{{ $t('auth.cta_login') }}</a>
                        </p>
                    </template>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ nickname: '', email: '', password: '', password_confirmation: '' });

function submit() {
    form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>
