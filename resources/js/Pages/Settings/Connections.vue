<template>
    <Head :title="$t('settings.connections_title')" />

    <settings-layout current="connections">
        <p class="text-sm text-muted">{{ $t('settings.connections_intro') }}</p>

        <!-- Above Discord on purpose: Discord is how you sign in, this is the
             name everything scored is read under, so it is the connection
             that decides whether events work at all. -->
        <u-card>
            <template #header>
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="font-semibold">{{ $t('profile.osrs_account') }}</span>
                    <!-- A typed name next to an OAuth-linked Discord account
                         reads as equally proven unless the difference is on
                         screen. Wise Old Man only knows accounts somebody has
                         looked up there, so unconfirmed is a normal state for
                         a real player — the badge is neutral, not an error. -->
                    <u-badge
                        :color="osrsVerified ? 'success' : 'warning'"
                        variant="subtle"
                        :icon="osrsVerified ? 'i-lucide-check' : 'i-lucide-triangle-alert'"
                        :label="osrsVerified ? $t('profile.osrs_confirmed') : $t('profile.osrs_unconfirmed')"
                    />
                </div>
            </template>

            <div class="flex items-start justify-between gap-4 flex-wrap">
                <p class="text-sm text-muted max-w-md">{{ $t('profile.osrs_account_help') }}</p>

                <div class="flex items-center gap-2 flex-wrap shrink-0">
                    <u-input
                        v-model="osrsInput"
                        :placeholder="$t('profile.osrs_username')"
                        size="sm"
                        maxlength="12"
                        icon="i-lucide-user-round"
                    />
                    <u-button
                        v-if="! osrsVerified"
                        size="sm"
                        color="neutral"
                        variant="outline"
                        :label="$t('auth.osrs_recheck')"
                        :loading="rechecking"
                        @click="recheckOsrs"
                    />
                    <u-button
                        size="sm"
                        color="primary"
                        icon="i-lucide-check"
                        :label="$t('common.save')"
                        :loading="osrsForm.processing"
                        @click="saveOsrsUsername"
                    />
                </div>
            </div>

            <p v-if="osrsForm.errors.osrs_username" class="text-sm text-error mt-2">{{ osrsForm.errors.osrs_username }}</p>
        </u-card>

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
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import { useAuth } from '@/Composables/useAuth';

const props = defineProps({
    hasDiscord: { type: Boolean, required: true },
    // For the disconnect guard, not for a password field — see the card above.
    hasPassword: { type: Boolean, required: true },
    osrsUsername: { type: String, default: null },
    osrsVerified: { type: Boolean, default: false },
});

const { user } = useAuth();

const osrsInput = ref(props.osrsUsername ?? '');
const osrsForm = useForm({ osrs_username: '' });

// Resynced after a save because the server may normalise what was typed —
// Wise Old Man returns the account's canonical casing, so "pondake" is stored
// as "Pondake". Seeded once, the field would keep showing the typed version
// and quietly disagree with what is actually saved.
watch(() => props.osrsUsername, (name) => (osrsInput.value = name ?? ''));

function saveOsrsUsername() {
    osrsForm.osrs_username = osrsInput.value.trim();
    osrsForm.put('/settings/connections/osrs', { preserveScroll: true });
}

const rechecking = ref(false);

// The same action the site-wide unconfirmed banner offers, on the page that
// owns the field — somebody who came here to fix the name should not have to
// wait for the banner to reappear to ask again.
function recheckOsrs() {
    rechecking.value = true;
    router.post('/settings/connections/osrs/verify', {}, {
        preserveScroll: true,
        onFinish: () => (rechecking.value = false),
        onError: (errors) => console.error(errors),
    });
}

function disconnectDiscord() {
    router.delete('/settings/account/discord', { preserveScroll: true });
}
</script>
