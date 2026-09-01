<template>
    <Head :title="$t('settings.connections_title')" />

    <settings-layout current="connections">
        <p class="text-sm text-muted">{{ $t('settings.connections_intro') }}</p>

        <u-card>
            <template #header>
                <span class="font-semibold">{{ $t('profile.discord_account') }}</span>
            </template>

            <div class="flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm text-muted">
                    {{ hasDiscord ? $t('profile.discord_connected_as', { name: user.discordUsername }) : $t('profile.no_discord_desc') }}
                </p>
                <!-- An account whose only way in is Discord may not unlink it,
                     so the button is disabled and says why rather than failing
                     on submit. DiscordController enforces the same rule
                     server-side — this is the courtesy, that is the guard. -->
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
    </settings-layout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import { useAuth } from '@/Composables/useAuth';

defineProps({
    hasDiscord: { type: Boolean, required: true },
    // For the disconnect guard, not for a password field — see the card above.
    hasPassword: { type: Boolean, required: true },
});

const { user } = useAuth();

function disconnectDiscord() {
    router.delete('/settings/account/discord', { preserveScroll: true });
}
</script>
