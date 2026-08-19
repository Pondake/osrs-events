<template>
    <Head :title="$t('auth.login_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.login_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.login_subtitle') }}</p>
                    </template>

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field :label="$t('auth.field_email')" :error="form.errors.email" required>
                            <u-input v-model="form.email" type="email" autocomplete="username" class="w-full" />
                        </u-form-field>

                        <u-form-field :label="$t('auth.field_password')" :error="form.errors.password" required>
                            <u-input v-model="form.password" type="password" autocomplete="current-password" class="w-full" />
                        </u-form-field>

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('auth.cta_login')" />
                    </form>

                    <template #footer>
                        <div class="flex items-center gap-3 text-xs text-muted uppercase tracking-wide">
                            <div class="h-px flex-1 bg-default" />
                            {{ $t('auth.or') }}
                            <div class="h-px flex-1 bg-default" />
                        </div>
                        <u-button :href="route('login')" color="neutral" variant="outline" block icon="i-simple-icons-discord" class="mt-4" :label="$t('auth.continue_with_discord')" />
                        <p class="text-center text-sm text-muted mt-4">
                            {{ $t('auth.no_account') }}
                            <a :href="route('auth.register')" class="text-primary hover:underline">{{ $t('auth.cta_register') }}</a>
                        </p>
                    </template>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '' });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>
