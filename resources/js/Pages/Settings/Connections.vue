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

            <p class="text-sm text-muted max-w-xl">{{ $t('profile.osrs_account_help') }}</p>
            <p class="text-sm text-muted max-w-xl mt-1">{{ $t('profile.osrs_alts_help') }}</p>

            <osrs-characters-field
                v-model="rows"
                :max="maxCharacters"
                :errors="osrsForm.errors"
                :proven="provenNames"
                size="sm"
                class="mt-4 max-w-lg"
            />

            <div class="flex items-center gap-2 flex-wrap mt-4">
                <u-button
                    size="sm"
                    color="primary"
                    icon="i-lucide-check"
                    :label="$t('common.save')"
                    :loading="osrsForm.processing"
                    :disabled="!characterNames(rows).length"
                    @click="saveCharacters"
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
            </div>

            <!-- A separate question from the badge above, and the one with a
                 consequence attached. Wise Old Man knowing the name says the
                 name exists; this says somebody played it from a client
                 holding this account's code. Only shown while the plugin is
                 something you can actually use — off, there is no way to act
                 on it and the notice would only nag. -->
            <u-alert
                v-if="pluginMode !== 'off' && osrsUsername"
                class="mt-4"
                :color="osrsProven ? 'success' : 'warning'"
                variant="subtle"
                :icon="osrsProven ? 'i-lucide-shield-check' : 'i-lucide-shield-alert'"
                :title="osrsProven ? $t('profile.osrs_proven') : $t('profile.osrs_unproven')"
                :description="osrsProven
                    ? $t('profile.osrs_proven_help', { name: osrsUsername, date: provenDate })
                    : $t('profile.osrs_unproven_help')"
            >
                <template v-if="! osrsProven" #actions>
                    <div class="w-full space-y-2">
                        <p class="text-sm">{{ $t('profile.osrs_unproven_how') }}</p>
                        <u-button
                            :href="route('settings.runelite')"
                            color="warning"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-plug"
                            :label="$t('profile.osrs_unproven_cta')"
                        />
                    </div>
                </template>
            </u-alert>
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
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import OsrsCharactersField from '@/Components/OsrsCharactersField.vue';
import { characterNames, characterRows } from '@/Support/osrsCharacters';
import { useAuth } from '@/Composables/useAuth';

const props = defineProps({
    hasDiscord: { type: Boolean, required: true },
    // For the disconnect guard, not for a password field — see the card above.
    hasPassword: { type: Boolean, required: true },
    osrsUsername: { type: String, default: null },
    osrsVerified: { type: Boolean, default: false },
    // Whether a RuneLite client has reported this account playing the name.
    osrsProven: { type: Boolean, default: false },
    provenAt: { type: String, default: null },
    pluginMode: { type: String, default: 'off' },
    // Every character, main first: [{ id, username, main, verified, proven }].
    characters: { type: Array, default: () => [] },
    maxCharacters: { type: Number, default: 5 },
});

const { user } = useAuth();

const provenDate = computed(() => (props.provenAt
    ? new Date(props.provenAt).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
    : ''));

const storedNames = computed(() => props.characters.map((character) => character.username));
const provenNames = computed(() => props.characters.filter((character) => character.proven).map((character) => character.username));

const rows = ref(characterRows(storedNames.value));
const osrsForm = useForm({ characters: [] });

// Resynced after a save because the server may normalise what was typed —
// Wise Old Man returns an account's canonical casing, so "pondake" is stored
// as "Pondake". Seeded once, the list would keep showing the typed version
// and quietly disagree with what is actually saved.
watch(storedNames, (names) => (rows.value = characterRows(names)));

function saveCharacters() {
    osrsForm.characters = characterNames(rows.value);
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
