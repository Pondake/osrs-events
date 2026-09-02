<template>
    <Head :title="$t('community.title')" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <div class="flex flex-col items-start gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ $t('community.title') }}</h1>
                        <p class="text-sm text-muted mt-1">{{ $t('community.subtitle') }}</p>
                    </div>
                </div>

                <!-- Same shape as the events hub (Boards/Index.vue): a slice
                     of each thing the nav group advertises, not one flat
                     list, so Teams/Leaderboards/Clans finally have a shared
                     landing spot the way Boards' hub already did. -->
                <div class="space-y-10">
                    <section>
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ $t('community.your_teams') }}</h2>
                            <u-button
                                v-if="teamsTotal > teams.length"
                                href="/teams"
                                size="sm"
                                variant="ghost"
                                color="neutral"
                                trailing-icon="i-lucide-arrow-right"
                                :label="$t('events.hub_view_all')"
                            />
                        </div>

                        <div v-if="!teams.length" class="text-center py-12 rounded-lg ring ring-default bg-default">
                            <u-icon name="i-lucide-users" class="size-10 text-dimmed mx-auto mb-3" />
                            <p class="text-sm text-muted mb-4">{{ $t('community.no_teams') }}</p>
                            <u-button href="/teams" size="sm" color="primary" :label="$t('teams.create_team')" />
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <u-card v-for="team in teams" :key="team.id">
                                <div class="flex items-center gap-3">
                                    <team-avatar :name="team.name" :icon-url="team.icon_url" :guild-icon-url="team.guild_icon_url" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold truncate">{{ team.name }}</div>
                                        <div class="text-xs text-muted truncate">
                                            {{ team.guild_name ?? $t('teams.no_server') }}
                                        </div>
                                    </div>
                                    <u-badge
                                        v-if="team.viewerRole && team.viewerRole !== 'MEMBER'"
                                        :label="$t(`teams.role_${team.viewerRole.toLowerCase()}`)"
                                        :color="team.viewerRole === 'OWNER' ? 'warning' : 'info'"
                                        variant="subtle"
                                        size="sm"
                                    />
                                </div>
                                <p class="text-sm text-muted mt-3">
                                    {{ $t('teams.member_count', { count: team.memberCount }) }}
                                </p>
                            </u-card>
                        </div>

                        <div v-if="teams.length" class="mt-4">
                            <u-button href="/teams" size="sm" variant="outline" color="neutral" :label="$t('teams.manage_members_short')" />
                        </div>
                    </section>

                    <!-- Advertised in the nav as Soon already; explaining what
                         it will actually show beats a section that silently
                         isn't there — same convention as the Boards hub's own
                         Calendar row. -->
                    <section v-for="soon in soonSections" :key="soon.key">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ soon.title }}</h2>
                            <u-badge :label="$t('nav.badge_soon')" color="neutral" variant="subtle" size="sm" />
                        </div>
                        <div class="rounded-lg ring ring-default bg-default px-5 py-8 text-center">
                            <u-icon :name="soon.icon" class="size-10 text-dimmed mx-auto mb-3" />
                            <p class="text-sm text-muted max-w-md mx-auto">{{ soon.desc }}</p>
                        </div>
                    </section>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import TeamAvatar from '@/Components/TeamAvatar.vue';

defineProps({
    teams: { type: Array, required: true },
    teamsTotal: { type: Number, required: true },
});

const soonSections = computed(() => [
    { key: 'leaderboards', title: trans('nav.leaderboards'), icon: 'i-lucide-trophy', desc: trans('community.leaderboards_desc') },
    { key: 'clans', title: trans('nav.clans'), icon: 'i-lucide-shield', desc: trans('community.clans_desc') },
]);
</script>
