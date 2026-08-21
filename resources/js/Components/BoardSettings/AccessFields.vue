<template>
    <div class="space-y-5 py-2">
        <u-form-field :label="$t('admin.access_mode')" :description="$t('admin.access_mode_desc')">
            <div class="flex flex-col gap-2">
                <button
                    v-for="option in accessOptions"
                    :key="option.value"
                    type="button"
                    class="flex items-start gap-3 p-3 rounded-lg ring text-left transition-colors cursor-pointer"
                    :class="form.access_mode === option.value ? 'ring-primary bg-primary/5' : 'ring-default hover:ring-primary/50'"
                    @click="form.access_mode = option.value"
                >
                    <u-icon :name="option.icon" class="size-5 shrink-0 mt-0.5" :class="form.access_mode === option.value ? 'text-primary' : 'text-muted'" />
                    <span class="min-w-0">
                        <span class="block font-medium text-sm">{{ option.label }}</span>
                        <span class="block text-xs text-muted mt-0.5">{{ option.description }}</span>
                    </span>
                </button>
            </div>
        </u-form-field>

        <!-- `required` for real, not just in the label text. GUILD access
             with no server picked is not a restriction — it is an event
             nobody can join, and the form said "required" while the server
             happily saved it empty. Both ends agree now
             (required_if:access_mode,GUILD). -->
        <u-form-field
            v-if="form.access_mode === 'GUILD'"
            :label="$t('admin.required_server')"
            :description="$t('admin.required_server_desc')"
            :error="form.errors.required_guild_id"
            required
        >
            <!-- A Discord server id is an 18-digit snowflake. This was a bare
                 text box, which asked the user to know or go and find one —
                 for a value we already hold, by name and icon, from the guild
                 sync that runs on every Discord login. -->
            <u-select
                v-if="loadingGuilds || guildOptions.length"
                v-model="form.required_guild_id"
                :items="guildOptions"
                :loading="loadingGuilds"
                :placeholder="guildPlaceholder"
                class="w-full"
            />

            <!-- An empty dropdown is the one thing this must not be: it looks
                 like the feature is broken when the actual situation is
                 either "no Discord on this account" or "Discord is linked but
                 we never got its server list", and those need different
                 actions. -->
            <u-alert
                v-else
                color="warning"
                variant="subtle"
                icon="i-simple-icons-discord"
                :title="hasDiscord ? $t('admin.guilds_none_title') : $t('admin.guilds_no_discord_title')"
                :description="hasDiscord ? $t('admin.guilds_none_desc') : $t('admin.guilds_no_discord_desc')"
                :actions="[{
                    label: hasDiscord ? $t('admin.guilds_reconnect') : $t('profile.connect_discord'),
                    color: 'warning',
                    variant: 'outline',
                    to: '/settings/account',
                }]"
            />
        </u-form-field>

        <!-- Only meaningful on an invite-only event, and only once it exists.
             Said here, on the step that made the choice, rather than as a
             whole tab that sits there on every event explaining itself. -->
        <u-alert
            v-if="!isEdit && form.access_mode === 'INVITE'"
            color="neutral"
            variant="subtle"
            icon="i-lucide-link"
            :description="$t('admin.invites_after_create')"
        />

        <u-separator />

        <u-form-field :description="$t('admin.board_listed_desc')">
            <u-switch v-model="form.is_listed" :label="$t('admin.board_listed')" />
        </u-form-field>

        <u-form-field :label="$t('admin.editors')" :description="$t('admin.editors_desc')">
            <div class="space-y-2">
                <u-input
                    :model-value="authorSearch"
                    icon="i-lucide-search"
                    :placeholder="$t('common.search')"
                    class="w-full"
                    @update:model-value="(v) => emit('update:authorSearch', v)"
                />

                <div v-if="authorResults.length" class="rounded-md ring ring-default divide-y divide-default">
                    <button
                        v-for="candidate in authorResults"
                        :key="candidate.id"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left"
                        @click="emit('add-author', candidate)"
                    >
                        <u-avatar :src="candidate.avatar_url ?? undefined" :alt="candidate.nickname ?? candidate.discord_username" size="xs" />
                        <span class="text-sm">{{ candidate.nickname ?? candidate.discord_username }}</span>
                    </button>
                </div>

                <div v-if="authors.length" class="flex flex-wrap gap-2">
                    <u-badge v-for="author in authors" :key="author.id" color="primary" variant="subtle" class="flex items-center gap-1">
                        {{ author.nickname ?? author.discord_username }}
                        <span v-if="author.id === currentUserId" class="opacity-70">({{ $t('admin.you_suffix') }})</span>
                        <button v-if="!author.is_owner" type="button" class="ml-1 hover:text-error" @click="emit('remove-author', author.id)">
                            <u-icon name="i-lucide-x" class="size-3" />
                        </button>
                    </u-badge>
                </div>
            </div>
        </u-form-field>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    form: { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
    guilds: { type: Array, default: () => [] },
    loadingGuilds: { type: Boolean, default: false },
    hasDiscord: { type: Boolean, default: false },
    authors: { type: Array, default: () => [] },
    authorSearch: { type: String, default: '' },
    authorResults: { type: Array, default: () => [] },
    currentUserId: { type: String, default: null },
});

const emit = defineEmits(['update:authorSearch', 'add-author', 'remove-author']);

const accessOptions = [
    {
        value: 'OPEN',
        label: trans('admin.access_mode_open'),
        description: trans('admin.access_mode_open_desc'),
        icon: 'i-lucide-globe',
    },
    {
        value: 'GUILD',
        label: trans('admin.access_mode_guild'),
        description: trans('admin.access_mode_guild_desc'),
        icon: 'i-simple-icons-discord',
    },
    {
        value: 'INVITE',
        label: trans('admin.access_mode_invite'),
        description: trans('admin.access_mode_invite_desc'),
        icon: 'i-lucide-link',
    },
];

const guildOptions = computed(() => props.guilds.map((guild) => ({ label: guild.name, value: guild.id })));

// Three states, three sentences: still loading, loaded and empty, loaded and
// populated. An empty picker with a "pick one" placeholder is the version
// that reads as broken.
const guildPlaceholder = computed(() => {
    if (props.loadingGuilds) return trans('common.loading');

    return guildOptions.value.length
        ? trans('admin.required_server_pick')
        : trans('admin.required_server_none');
});
</script>
