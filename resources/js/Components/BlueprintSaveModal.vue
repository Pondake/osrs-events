<template>
    <u-button
        :size="size"
        :color="color"
        :variant="variant"
        icon="i-lucide-layout-template"
        :label="$t('blueprints.save_as_template')"
        @click="open()"
    />

    <u-modal v-model:open="isOpen" :title="$t('blueprints.save_as_template')">
        <template #body>
            <div class="space-y-4 py-2">
                <p class="text-sm text-muted">{{ $t('blueprints.save_desc') }}</p>

                <u-form-field :label="$t('blueprints.name')" :error="errors.title" required>
                    <u-input v-model="title" class="w-full" :placeholder="eventTitle" />
                </u-form-field>

                <u-form-field :label="$t('blueprints.note')" :description="$t('blueprints.note_desc')">
                    <u-textarea v-model="description" class="w-full" :rows="2" />
                </u-form-field>

                <u-form-field :label="$t('blueprints.who_for')" :description="$t('blueprints.who_for_desc')" :error="errors.guild_id">
                    <u-select
                        v-if="guildOptions.length > 1"
                        v-model="guildId"
                        :items="guildOptions"
                        :loading="loadingGuilds"
                        class="w-full"
                    />
                    <p v-else class="text-sm text-muted">{{ $t('blueprints.who_for_just_you') }}</p>
                </u-form-field>

                <!-- Said before it is saved, because it is the thing people
                     assume works the other way: a template is a photograph of
                     the event, not a link to it. -->
                <u-alert
                    color="neutral"
                    variant="subtle"
                    icon="i-lucide-camera"
                    :description="$t('blueprints.copy_not_link')"
                />
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :loading="saving" :label="$t('blueprints.save')" @click="save" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';

/**
 * Saving an event as a reusable format.
 *
 * Offered in two places, which is deliberate: while editing, because that is
 * when a host is thinking about the settings, and once an event has finished,
 * because that is when they know whether the format was worth keeping.
 *
 * A copy, not a link — decided 2026-08-22. The settings are read once, on the
 * server, and the event and the template go their separate ways afterwards.
 */
const props = defineProps({
    eventId: { type: String, required: true },
    eventTitle: { type: String, default: '' },
    size: { type: String, default: 'sm' },
    color: { type: String, default: 'neutral' },
    variant: { type: String, default: 'outline' },
});

// Loaded after hydration, never during SSR. useToast statically imports a
// virtual '#imports' specifier that only resolves through the ui() Vite
// plugin, and Vite's SSR build externalizes node_modules — so a top-level
// import here crashes the whole SSR process at startup, whatever page is
// being rendered. AppRoot.vue carries the long version of this note.
let toast;

onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');

    toast = useToast();
});

const isOpen = ref(false);
const saving = ref(false);
const errors = ref({});

const title = ref('');
const description = ref('');
const guildId = ref('');
const guilds = ref([]);
const loadingGuilds = ref(false);

/** "Just me" first — a format is personal until somebody says otherwise. */
const guildOptions = computed(() => [
    { value: '', label: trans('blueprints.who_for_me') },
    ...guilds.value.map((guild) => ({ value: guild.id, label: guild.name })),
]);

function open() {
    // Prefilled with the event's own name, which is right often enough to be
    // worth typing over rather than typing out.
    title.value = props.eventTitle;
    description.value = '';
    guildId.value = '';
    errors.value = {};
    isOpen.value = true;

    loadGuilds();
}

async function loadGuilds() {
    if (guilds.value.length) return;

    loadingGuilds.value = true;

    try {
        const response = await fetch('/my-guilds', { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error(`guild lookup failed: ${response.status}`);

        guilds.value = (await response.json()).guilds ?? [];
    } catch (error) {
        // The field is optional and saving works without it, so this degrades
        // to "just you" rather than blocking the dialog.
        console.error(error);
        guilds.value = [];
    } finally {
        loadingGuilds.value = false;
    }
}

async function save() {
    saving.value = true;
    errors.value = {};

    try {
        const response = await fetch(`/events/${props.eventId}/blueprint`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
            body: JSON.stringify({
                title: title.value,
                description: description.value || null,
                guild_id: guildId.value || null,
            }),
        });

        if (response.status === 422) {
            const body = await response.json();

            // Flattened to one message per field, which is what the form
            // fields above render.
            errors.value = Object.fromEntries(
                Object.entries(body.errors ?? {}).map(([field, list]) => [field, list[0]]),
            );

            return;
        }

        if (!response.ok) throw new Error(`saving the template failed: ${response.status}`);

        isOpen.value = false;
        toast?.add({
            id: 'blueprint-save',
            title: trans('blueprints.saved'),
            description: trans('blueprints.saved_desc'),
            color: 'success',
        });
    } catch (error) {
        console.error(error);
        toast?.add({
            id: 'blueprint-save-error',
            title: trans('blueprints.save_failed'),
            color: 'error',
        });
    } finally {
        saving.value = false;
    }
}
</script>
