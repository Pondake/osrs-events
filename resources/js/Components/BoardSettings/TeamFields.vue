<template>
    <div class="py-2 space-y-4">
        <p class="text-sm text-muted">{{ $t('admin.team_assignment_desc') }}</p>

        <!-- The one case that really is blocked, and it isn't blocked by this
             form: you cannot assign a team you don't have. Says so, links to
             where teams are made, and points out this can wait — an event
             does not have to be complete to be created. -->
        <u-alert
            v-if="!loading && !assigned.length && !available.length"
            color="neutral"
            variant="subtle"
            icon="i-lucide-users"
            :title="$t('admin.teams_none_title')"
            :description="$t('admin.teams_none_desc')"
            :actions="[{ label: $t('teams.create_team'), color: 'neutral', variant: 'outline', to: '/teams' }]"
        />

        <template v-else>
            <div class="flex gap-2">
                <!-- The server name rides along in the label: two teams
                     called "Main" from different clans are otherwise the
                     same row twice. -->
                <u-select
                    :model-value="modelValue"
                    :items="available.map((t) => ({ label: t.guild_name ? `${t.name} · ${t.guild_name}` : t.name, value: t.id }))"
                    :placeholder="$t('admin.select_team_placeholder')"
                    :loading="loading"
                    class="w-full"
                    @update:model-value="(v) => emit('update:modelValue', v)"
                />
                <u-button icon="i-lucide-plus" :disabled="!modelValue" :loading="adding" @click="emit('add')" />
            </div>

            <!-- Headed and counted. The list was an unlabelled box under a
                 dropdown, so "which teams did I actually pick" had no answer
                 on screen — reported as not being able to see what was
                 chosen or loaded. -->
            <div>
                <p class="text-xs font-medium text-muted uppercase tracking-wide mb-2">
                    {{ $t('admin.teams_assigned_count', { count: assigned.length }) }}
                </p>

                <div class="divide-y divide-default rounded-md ring ring-default">
                    <div v-for="team in assigned" :key="team.id" class="flex items-center gap-3 px-3 py-2">
                        <u-icon name="i-lucide-users" class="size-4 text-primary shrink-0" />
                        <span class="text-sm min-w-0 flex-1 truncate">{{ team.name }}</span>
                        <!-- Which Discord server it belongs to, since that
                             is what decides who can see it at all. A team
                             without one is private to its members. -->
                        <u-badge
                            :label="team.guild_name || $t('teams.private_team')"
                            :color="team.guild_name ? 'info' : 'neutral'"
                            variant="subtle"
                            size="sm"
                            class="shrink-0"
                        />
                        <u-button icon="i-lucide-x" size="xs" color="error" variant="ghost" :aria-label="$t('common.remove')" @click="emit('remove', team)" />
                    </div>
                    <p v-if="!assigned.length" class="px-3 py-4 text-center text-sm text-muted">{{ $t('admin.no_teams_assigned') }}</p>
                </div>
            </div>

            <!-- Says the two things people were left guessing at: this is not
                 the last chance, and members are managed elsewhere. Adding a
                 whole team-builder to this step would put a second CRUD
                 inside a five-step form. -->
            <u-alert
                color="neutral"
                variant="subtle"
                icon="i-lucide-info"
                :description="isEdit ? $t('admin.team_assignment_later_edit') : $t('admin.team_assignment_later')"
                :actions="[{ label: $t('nav.teams'), color: 'neutral', variant: 'outline', to: '/teams', target: '_blank' }]"
            />
        </template>
    </div>
</template>

<script setup>
defineProps({
    assigned: { type: Array, default: () => [] },
    available: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    adding: { type: Boolean, default: false },
    isEdit: { type: Boolean, default: false },
    // The team currently picked in the dropdown, not yet added.
    modelValue: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'add', 'remove']);
</script>
