<template>
    <div class="space-y-5 py-2">
        <!-- Said out loud rather than by disappearing. The field used to be
             hidden entirely while the site switch was off, so a host who went
             looking for it found nothing and had no way to learn why —
             reported 2026-09-12 after a whole bingo was played with a working
             webhook and one message to show for it. -->
        <u-alert
            v-if="!discordWebhooksEnabled"
            color="neutral"
            variant="subtle"
            icon="i-lucide-power-off"
            :description="$t('announcements.disabled_site_wide')"
        />

        <template v-else>
            <u-form-field
                :label="$t('announcements.webhook_label')"
                :description="$t('announcements.webhook_desc')"
                :error="form.errors.discord_webhook_url"
            >
                <u-input
                    v-model="form.discord_webhook_url"
                    type="url"
                    icon="i-lucide-webhook"
                    placeholder="https://discord.com/api/webhooks/…"
                    class="w-full"
                />
            </u-form-field>

            <u-separator />

            <!-- The list is the server's, per event type: a skill race is not
                 offered "somebody finished", because nothing in a race ever
                 will. An unticked box for something that cannot happen
                 teaches a host that the whole list is decorative. -->
            <u-form-field :label="$t('announcements.triggers_label')" :description="triggersDescription">
                <div class="flex flex-col gap-3 mt-1">
                    <label
                        v-for="trigger in available"
                        :key="trigger.key"
                        class="flex items-start gap-3 cursor-pointer"
                    >
                        <u-checkbox
                            :model-value="chosen.includes(trigger.key)"
                            class="mt-0.5 shrink-0"
                            @update:model-value="toggle(trigger.key, $event)"
                        />
                        <u-icon :name="trigger.icon" class="size-4 text-muted shrink-0 mt-1" />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{ $t(`announcements.trigger_${trigger.key}`) }}</span>
                            <span class="block text-xs text-muted">{{ $t(`announcements.trigger_${trigger.key}_desc`) }}</span>
                        </span>
                    </label>
                </div>
            </u-form-field>

            <!-- Nothing ticked is a valid answer and a strange one, so it
                 says so rather than looking like the form failed to load. -->
            <u-alert
                v-if="chosen.length === 0 && form.discord_webhook_url"
                color="warning"
                variant="subtle"
                icon="i-lucide-bell-off"
                :description="$t('announcements.nothing_selected')"
            />
        </template>
    </div>
</template>

<script setup>
/**
 * The Announcements tab: where the channel goes, and what reaches it.
 *
 * Its own tab rather than a row under Access, which is about who may open the
 * event — a different question from who hears about it, and the reason the
 * webhook field went unfound for a fortnight.
 */
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    form: { type: Object, required: true },
    // Null for a non-editor; this tab is not rendered for them at all.
    settings: { type: Object, default: null },
    discordWebhooksEnabled: { type: Boolean, default: false },
});

const available = computed(() => props.settings?.available ?? []);

// The form owns the selection once the tab has been opened; `settings.chosen`
// is only the starting point the server resolved (null -> catalogue defaults).
const chosen = computed(() => props.form.discord_announcements ?? props.settings?.chosen ?? []);

const triggersDescription = computed(() => trans('announcements.triggers_desc', { count: available.value.length }));

function toggle(key, on) {
    const next = new Set(chosen.value);

    if (on) next.add(key);
    else next.delete(key);

    // Written back as an array in the catalogue's own order, so two hosts who
    // tick the same boxes store the same value and the diff of an event stays
    // readable.
    props.form.discord_announcements = available.value
        .map((trigger) => trigger.key)
        .filter((key_) => next.has(key_));
}
</script>
