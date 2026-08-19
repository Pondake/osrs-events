<template>
    <Head :title="$t('settings.account_title')" />

    <settings-layout current="account">
        <u-card>
            <template #header>
                <span class="font-semibold">{{ $t('profile.discord_account') }}</span>
            </template>

            <div class="flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm text-muted">
                    {{ hasDiscord ? $t('profile.discord_connected_as', { name: user.discordUsername }) : $t('profile.no_discord_desc') }}
                </p>
                <u-button
                    v-if="hasDiscord"
                    :disabled="!hasPassword"
                    color="neutral"
                    variant="outline"
                    size="sm"
                    :label="$t('profile.disconnect_discord')"
                    :title="!hasPassword ? $t('profile.discord_disconnect_needs_password') : undefined"
                    @click="disconnectDiscord"
                />
                <u-button v-else :href="route('settings.discord.connect')" color="primary" variant="outline" size="sm" icon="i-simple-icons-discord" :label="$t('profile.connect_discord')" />
            </div>
        </u-card>

        <u-card>
            <template #header>
                <span class="font-semibold">{{ hasPassword ? $t('profile.change_password') : $t('profile.set_password') }}</span>
            </template>

            <p v-if="!hasPassword" class="text-sm text-muted mb-3">{{ $t('profile.no_password_desc') }}</p>
            <p v-if="email" class="text-sm text-muted mb-4">{{ $t('auth.field_email') }}: {{ email }}</p>

            <form class="space-y-3 max-w-sm" @submit.prevent="submitPassword">
                <u-form-field v-if="hasPassword" :label="$t('profile.current_password')" :error="passwordForm.errors.current_password" required>
                    <u-input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="w-full" />
                </u-form-field>
                <u-form-field :label="$t('profile.new_password')" :description="$t('auth.password_requirements')" :error="passwordForm.errors.password" required>
                    <u-input v-model="passwordForm.password" type="password" autocomplete="new-password" class="w-full" />
                </u-form-field>
                <u-form-field :label="$t('profile.confirm_new_password')" required>
                    <u-input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
                </u-form-field>
                <u-button
                    type="submit"
                    color="primary"
                    size="sm"
                    :loading="passwordForm.processing"
                    :label="hasPassword ? $t('profile.change_password') : $t('profile.set_password')"
                />
            </form>
        </u-card>
    </settings-layout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';
import SettingsLayout from '@/Components/SettingsLayout.vue';

defineProps({
    email: { type: String, default: null },
    hasPassword: { type: Boolean, required: true },
    hasDiscord: { type: Boolean, required: true },
});

const { user } = useAuth();

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function submitPassword() {
    passwordForm.put('/settings/account/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function disconnectDiscord() {
    router.delete('/settings/account/discord', { preserveScroll: true });
}
</script>
