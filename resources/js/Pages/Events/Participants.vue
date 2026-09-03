<template>
    <Head :title="$t('participants.title', { event: event.title })">
        <!-- Never indexed. Whether or not names render for a given viewer,
             a page called "who is playing this" has no business in search
             results. -->
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <div class="flex items-start justify-between gap-4 flex-wrap mb-8">
                    <div class="min-w-0">
                        <a :href="`/events/${event.id}`" class="text-sm text-muted hover:text-primary transition-colors inline-flex items-center gap-1.5">
                            <u-icon name="i-lucide-arrow-left" class="size-4" />
                            {{ event.title }}
                        </a>
                        <h1 class="text-3xl font-bold text-highlighted mt-1">{{ $t('participants.heading') }}</h1>
                        <p class="text-sm text-muted mt-1">
                            {{ $t('participants.count', { count: participantCount }) }}
                        </p>
                    </div>

                    <u-button
                        v-if="canEdit"
                        :href="`/events/${event.id}`"
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-settings"
                        :label="$t('board.event_settings')"
                    />
                </div>

                <!-- The reason a stranger sees a number and no names. Said
                     out loud rather than left as an empty list, which would
                     read as "nobody is playing". -->
                <u-alert
                    v-if="!named"
                    class="mb-8"
                    color="neutral"
                    variant="subtle"
                    icon="i-lucide-eye-off"
                    :title="$t('participants.private_title')"
                    :description="$t('participants.private_desc')"
                />

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <u-card v-if="event.mode === 'TEAM'" :ui="{ body: 'p-0 sm:p-0' }">
                        <template #header>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold">{{ $t('participants.teams') }}</span>
                                <u-badge :label="String(teams.length)" color="neutral" variant="subtle" size="sm" />
                            </div>
                        </template>

                        <ul v-if="teams.length" class="divide-y divide-default">
                            <li v-for="team in teams" :key="team.id">
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-elevated transition-colors text-left"
                                    @click="expanded = expanded === team.id ? null : team.id"
                                >
                                    <team-avatar :name="team.name" :icon-url="team.iconUrl" :guild-icon-url="team.guildIconUrl" size="sm" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-medium truncate">{{ team.name }}</span>
                                        <span class="block text-xs text-muted truncate">
                                            {{ team.guildName || $t('teams.private_team') }} ·
                                            {{ $t('participants.member_count', { count: team.memberCount }) }}
                                        </span>
                                    </span>
                                    <u-icon
                                        :name="expanded === team.id ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                                        class="size-4 text-muted shrink-0"
                                    />
                                </button>

                                <div v-if="expanded === team.id" class="px-4 pb-3 space-y-2">
                                    <div v-for="member in team.members" :key="member.id" class="flex items-center gap-2 text-sm">
                                        <u-avatar :src="member.avatarUrl ?? undefined" :alt="member.name" size="2xs" />
                                        <span class="truncate">{{ member.name }}</span>
                                        <u-badge
                                            v-if="member.role !== 'MEMBER'"
                                            :label="$t(`teams.role_${member.role.toLowerCase()}`)"
                                            :color="member.role === 'OWNER' ? 'warning' : 'info'"
                                            variant="subtle"
                                            size="sm"
                                        />
                                    </div>

                                    <p v-if="!team.members.length" class="text-xs text-muted italic">
                                        {{ named ? $t('teams.no_members') : $t('participants.members_hidden') }}
                                    </p>

                                    <!-- Where you hand somebody the right to
                                         run this team — the one page where
                                         you are already looking at who is in
                                         it. -->
                                    <u-button
                                        v-if="team.canManage"
                                        href="/teams"
                                        size="xs"
                                        color="neutral"
                                        variant="outline"
                                        icon="i-lucide-users-round"
                                        :label="$t('participants.manage_team')"
                                    />
                                </div>
                            </li>
                        </ul>

                        <div v-else class="px-4 py-8 text-center">
                            <p class="text-sm text-muted mb-3">{{ $t('participants.no_teams') }}</p>
                            <u-button
                                v-if="canEdit"
                                :href="`/events/${event.id}`"
                                size="sm"
                                color="primary"
                                icon="i-lucide-plus"
                                :label="$t('participants.assign_teams')"
                            />
                        </div>
                    </u-card>

                    <u-card :ui="{ body: 'p-0 sm:p-0' }">
                        <template #header>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold">{{ $t('participants.people') }}</span>
                                <u-badge :label="String(participantCount)" color="neutral" variant="subtle" size="sm" />
                            </div>
                        </template>

                        <ul v-if="participants.length" class="divide-y divide-default">
                            <li v-for="person in participants" :key="person.id" class="flex items-center gap-3 px-4 py-2.5">
                                <u-avatar :src="person.avatarUrl ?? undefined" :alt="person.name" size="sm" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">{{ person.name }}</span>
                                    <span v-if="person.osrsUsername" class="block text-xs text-muted truncate">{{ person.osrsUsername }}</span>
                                </span>
                                <u-badge v-if="person.isHost" :label="$t('participants.host')" color="warning" variant="subtle" size="sm" />
                            </li>
                        </ul>

                        <p v-else class="px-4 py-8 text-center text-sm text-muted">
                            {{ named ? $t('participants.nobody_yet') : $t('participants.members_hidden') }}
                        </p>
                    </u-card>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import TeamAvatar from '@/Components/TeamAvatar.vue';

const props = defineProps({
    event: { type: Object, required: true },
    teams: { type: Array, default: () => [] },
    participants: { type: Array, default: () => [] },
    participantCount: { type: Number, default: 0 },
    // False for a stranger looking at a public event: counts render, names
    // do not. See ParticipantController for why.
    named: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
});

const expanded = ref(null);

const breadcrumbs = computed(() => [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events'), href: '/events' },
    { label: props.event.title, href: `/events/${props.event.id}` },
    { label: trans('participants.heading') },
]);
</script>
