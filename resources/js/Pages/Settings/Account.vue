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
                <span class="font-semibold">{{ $t('profile.email_address') }}</span>
            </template>

            <p v-if="!email" class="text-sm text-muted mb-3">{{ $t('profile.email_needed_desc') }}</p>

            <form class="max-w-sm space-y-3" @submit.prevent="submitEmail">
                <u-form-field :error="emailForm.errors.email">
                    <u-input v-model="emailForm.email" type="email" autocomplete="email" class="w-full" />
                </u-form-field>

                <!-- Once there is a password, this address is what a reset
                     link goes to — so changing it is as sensitive as changing
                     the password, and takes the same proof. A Discord login
                     has no password to give and does not see this.
                     AccountController enforces the same rule server-side. -->
                <u-form-field
                    v-if="hasPassword"
                    :label="$t('profile.current_password')"
                    :description="$t('profile.email_needs_password')"
                    :error="emailForm.errors.current_password"
                    required
                >
                    <u-input v-model="emailForm.current_password" type="password" autocomplete="current-password" class="w-full" />
                </u-form-field>

                <u-button type="submit" color="primary" size="sm" :loading="emailForm.processing" :label="$t('profile.save_email')" />
            </form>
        </u-card>

        <u-card>
            <template #header>
                <span class="font-semibold">{{ hasPassword ? $t('profile.change_password') : $t('profile.set_password') }}</span>
            </template>

            <!-- No email means no way to receive a reset link, so a password
                 here would be unrecoverable — AccountController enforces the
                 same rule server-side. -->
            <u-alert
                v-if="!email"
                color="warning"
                variant="subtle"
                icon="i-lucide-mail"
                :description="$t('profile.password_needs_email')"
            />

            <template v-else>
                <p v-if="!hasPassword" class="text-sm text-muted mb-3">{{ $t('profile.no_password_desc') }}</p>

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

                    <!-- Said out loud rather than left to be discovered.
                         Changing a password is what somebody does when they
                         think a session is not theirs, so what it ends is the
                         thing they need to know. -->
                    <p v-if="hasPassword" class="text-xs text-muted">{{ $t('profile.password_change_signs_out_elsewhere') }}</p>
                </form>
            </template>
        </u-card>

        <!-- Closing the account.

             Last on the page and visibly separate, because everything above
             it is reversible and this is not. The decisions are rendered
             inline rather than behind a wizard: the thing somebody weighing
             this needs is the whole cost on one screen, not a first step. -->
        <u-card :ui="{ root: 'ring-error/30' }">
            <template #header>
                <div class="flex items-center gap-2">
                    <u-icon name="i-lucide-triangle-alert" class="size-4 shrink-0 text-error" />
                    <span class="font-semibold text-error">{{ $t('profile.delete_account') }}</span>
                </div>
            </template>

            <p class="text-sm text-muted mb-4">{{ $t('profile.delete_account_desc') }}</p>

            <!-- Events still running. Each needs an answer, and the answer is
                 either a person or an ending — there is no third option and
                 no default, so the select starts empty. -->
            <div v-if="deletion.events.length" class="space-y-3 mb-6">
                <p class="text-sm font-medium text-highlighted">{{ $t('profile.delete_events_heading') }}</p>

                <div v-for="event in deletion.events" :key="event.id" class="border border-default rounded-md p-3 space-y-2">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-highlighted">{{ event.title }}</p>
                            <p class="text-xs text-muted">
                                {{ event.participants === 0
                                    ? $t('profile.delete_event_nobody')
                                    : $t('profile.delete_event_players', { count: event.participants }) }}
                            </p>
                        </div>
                    </div>

                    <client-only>
                        <u-select
                            v-model="form.events[event.id]"
                            :items="choicesFor(event)"
                            :placeholder="$t('profile.delete_choose')"
                            class="w-full"
                        />
                    </client-only>

                    <p v-if="!event.candidates.length" class="text-xs text-warning">
                        {{ $t('profile.delete_no_candidates') }}
                    </p>
                </div>
            </div>

            <div v-if="deletion.teams.length" class="space-y-3 mb-6">
                <p class="text-sm font-medium text-highlighted">{{ $t('profile.delete_teams_heading') }}</p>

                <div v-for="team in deletion.teams" :key="team.id" class="border border-default rounded-md p-3 space-y-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-highlighted">{{ team.name }}</p>
                        <p class="text-xs text-muted">{{ $t('profile.delete_team_members', { count: team.members }) }}</p>
                    </div>

                    <client-only>
                        <u-select
                            v-model="form.teams[team.id]"
                            :items="choicesFor(team)"
                            :placeholder="$t('profile.delete_choose')"
                            class="w-full"
                        />
                    </client-only>

                    <p v-if="!team.candidates.length" class="text-xs text-warning">
                        {{ $t('profile.delete_no_candidates') }}
                    </p>
                </div>
            </div>

            <!-- Said before the button, not after: "everything is deleted" is
                 not quite true, and finding that out afterwards is worse than
                 reading it now. -->
            <u-alert
                v-if="deletion.keptEvents"
                color="neutral"
                variant="subtle"
                icon="i-lucide-history"
                class="mb-4"
                :description="$t('profile.delete_history_kept', { count: deletion.keptEvents })"
            />

            <form class="space-y-3 max-w-sm" @submit.prevent="submitDelete">
                <u-form-field
                    v-if="hasPassword"
                    :label="$t('profile.current_password')"
                    :error="form.errors.current_password"
                    required
                >
                    <u-input v-model="form.current_password" type="password" autocomplete="current-password" class="w-full" />
                </u-form-field>

                <u-form-field
                    :label="$t('profile.delete_confirm_label', { name: osrsUsername })"
                    :description="$t('profile.delete_confirm_help')"
                    :error="form.errors.confirmation"
                    required
                >
                    <u-input v-model="form.confirmation" class="w-full" autocomplete="off" />
                </u-form-field>

                <u-button
                    type="submit"
                    color="error"
                    size="sm"
                    icon="i-lucide-trash-2"
                    :loading="form.processing"
                    :disabled="!ready"
                    :label="$t('profile.delete_account')"
                />
            </form>
        </u-card>
    </settings-layout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const props = defineProps({
    email: { type: String, default: null },
    hasPassword: { type: Boolean, required: true },
    hasDiscord: { type: Boolean, required: true },
    osrsUsername: { type: String, default: '' },
    deletion: {
        type: Object,
        default: () => ({ events: [], teams: [], keptEvents: 0 }),
    },
});

const { user } = useAuth();

const emailForm = useForm({ email: props.email ?? '', current_password: '' });

function submitEmail() {
    emailForm.put('/settings/account/email', {
        preserveScroll: true,
        // Never leave a password sitting in a form field after the round
        // trip, whichever way it went.
        onFinish: () => (emailForm.current_password = ''),
    });
}

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

// --- closing the account -------------------------------------------------

const form = useForm({
    confirmation: '',
    current_password: '',
    // Keyed by id so the server gets a decision per thing rather than a list
    // it has to line up with one it built separately.
    events: {},
    teams: {},
});

/**
 * Hand it over, or end it. Deliberately no pre-selected option: whichever one
 * this defaulted to would be the wrong thing to do by accident.
 */
function choicesFor(thing) {
    return [
        ...thing.candidates.map((candidate) => ({
            label: trans('profile.delete_transfer_to', { name: candidate.name }),
            value: candidate.id,
        })),
        { label: trans('profile.delete_end_it'), value: 'delete' },
    ];
}

/**
 * Every owned thing answered, and the name typed out. The server checks all
 * of this again — this only stops somebody pressing a button that was always
 * going to fail.
 */
const ready = computed(() => {
    const answered = (list, answers) => list.every((thing) => Boolean(answers[thing.id]));

    return (
        form.confirmation.trim().length > 0 &&
        answered(props.deletion.events, form.events) &&
        answered(props.deletion.teams, form.teams)
    );
});

function submitDelete() {
    form.delete('/settings/account', {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
        onFinish: () => (form.current_password = ''),
    });
}
</script>
