<template>
    <!-- CLAUDE.md's convention is a stepper for create and tabs for edit.
         Neither applies to four fields with no sections to split them into —
         a one-step stepper is a form with extra chrome. Same deliberate
         deviation BoardSettingsModal documents, for the same reason: the
         rule exists to keep long forms navigable, and this form is not one.
         What the convention actually asks for — ONE component for create and
         edit rather than a separate create page — is what this is. -->
    <u-modal v-model:open="isOpen" :title="isEdit ? $t('teams.edit_team') : $t('teams.create_team')" :dismissible="false">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('teams.team_name')" :error="form.errors.name" required>
                    <u-input v-model="form.name" class="w-full" :placeholder="$t('teams.team_name_placeholder')" />
                </u-form-field>

                <u-form-field :label="$t('teams.team_icon')">
                    <wiki-icon-picker v-model="form.icon_url" />
                </u-form-field>

                <!-- A picker over the servers this account is actually in,
                     not two text boxes asking for an 18-digit snowflake and
                     its name — the same fix the event form's guild field
                     got, and for the same reason: we already hold both,
                     synced on every Discord login.

                     It decides who can SEE the team, which is why it is
                     described as a visibility choice rather than as
                     metadata. No server means private: only members see it.

                     Optional, deliberately. Linking Discord is not required
                     to use this site, and an account without it must still
                     be able to run a team. -->
                <u-form-field :label="$t('teams.discord_server')" :description="$t('teams.discord_server_desc')">
                    <u-select
                        v-if="guildOptions.length > 1"
                        v-model="form.guild_id"
                        :items="guildOptions"
                        :avatar="selectedGuildAvatar"
                        :loading="loadingGuilds"
                        class="w-full"
                    />

                    <u-alert
                        v-else
                        color="neutral"
                        variant="subtle"
                        icon="i-simple-icons-discord"
                        :description="loadingGuilds ? $t('common.loading') : $t('teams.no_guilds_desc')"
                    />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button
                    color="primary"
                    :label="isEdit ? $t('common.save') : $t('common.create')"
                    :loading="form.processing"
                    @click="submit"
                />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import WikiIconPicker from '@/Components/WikiIconPicker.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    // null is create mode. Same convention as BoardSettingsModal's `board`.
    team: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEdit = computed(() => props.team !== null);

const blank = () => ({ name: '', icon_url: '', guild_id: '' });

const form = useForm(blank());

// The Discord servers this account is in. Fetched once when the modal first
// mounts rather than per open — the list changes only on a Discord login.
const guilds = ref([]);
const loadingGuilds = ref(false);

// Every server gets an `avatar`, even one with no icon — `src: undefined`
// makes UAvatar fall back to initials from `alt` rather than render a broken
// image. Guild icons are optional on Discord's side, and Discord itself shows
// initials for a server without one, so this matches where the names come
// from. Skipping the avatar entirely for those (the first version of this)
// left them flush against the edge while every other row was indented by the
// avatar slot — visible the moment a real list was on screen.
const guildOptions = computed(() => [
    { label: trans('teams.no_guild'), value: '' },
    ...guilds.value.map((guild) => ({
        label: guild.name,
        value: guild.id,
        avatar: { src: guild.icon_url ?? undefined, alt: guild.name },
    })),
]);

// The closed select renders its OWN `avatar` prop, never the selected item's
// (Select.vue's trigger checks `props.avatar`; only the dropdown list reads
// `item.avatar`). Without this, opening an existing team shows the server's
// name but not its icon — which is most of what "show the linked server"
// asks for.
const selectedGuildAvatar = computed(
    () => guildOptions.value.find((option) => option.value === form.guild_id)?.avatar,
);

onMounted(async () => {
    loadingGuilds.value = true;

    try {
        const response = await fetch('/my-guilds', { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error(`guild lookup failed: ${response.status}`);

        guilds.value = (await response.json()).guilds ?? [];
    } catch (error) {
        // The field is optional and the form works without it, so this
        // degrades to the "no servers" note rather than blocking a save.
        console.error(error);
    } finally {
        loadingGuilds.value = false;
    }
});

// Re-seeded on every switch between teams (and back to create), the same way
// BoardSettingsModal does it — one modal instance is reused for every row on
// the page, so without this it keeps whichever team was opened first.
//
// Picked field by field rather than spread wholesale: the index ships each
// team with its members and permission flags attached, and a blind spread
// would post that entire object back on save. Nulls become '' because
// u-input binds a string and renders a null as the literal word "null".
watch(
    () => props.team,
    (team) => {
        form.defaults(team
            ? Object.fromEntries(Object.keys(blank()).map((key) => [key, team[key] ?? '']))
            : blank());
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (isOpen.value = false) };

    if (isEdit.value) {
        form.patch(`/teams/${props.team.id}`, options);
    } else {
        form.post('/teams', { ...options, onSuccess: () => { isOpen.value = false; form.reset(); } });
    }
}
</script>
