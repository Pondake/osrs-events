<template>
    <div class="space-y-4 py-2">
        <!-- Blueprints (Admin > Blueprints) are the formats a clan reuses —
             "Skill of the Week", "Boss of the Month". Suggested on the title
             field rather than made into a picker step of their own, because
             the thing being reused IS the title; picking one fills in the
             type, metric and description it carries and leaves the rest
             alone, and typing straight past it is still an ordinary text
             field.

             Create only: an existing event's type is locked, so a suggestion
             that changes it would offer something the form refuses to
             apply. -->
        <u-form-field :label="$t('admin.board_title')" :error="form.errors.title" required>
            <div class="space-y-2">
                <u-input
                    :model-value="form.title"
                    class="w-full"
                    :placeholder="$t('admin.board_title_placeholder')"
                    @update:model-value="onTitle"
                    @focus="emit('search-blueprints', form.title)"
                />

                <div v-if="!isEdit && blueprints.length" class="rounded-md ring ring-default divide-y divide-default max-h-52 overflow-y-auto">
                    <button
                        v-for="blueprint in blueprints"
                        :key="blueprint.id"
                        type="button"
                        class="w-full flex items-start gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left"
                        @click="emit('apply-blueprint', blueprint)"
                    >
                        <u-icon name="i-lucide-shapes" class="size-4 text-primary shrink-0 mt-0.5" />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium truncate">{{ blueprint.title }}</span>
                            <span v-if="blueprint.description" class="block text-xs text-muted line-clamp-1">{{ blueprint.description }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </u-form-field>

        <u-form-field :label="$t('admin.board_description')" :description="$t('admin.board_description_desc')">
            <u-textarea v-model="form.description" class="w-full" :rows="3" />
        </u-form-field>

        <!-- Pre-filled with today and a fortnight out rather than left blank
             and then demanded on submit. An event needs a window — every
             status badge, every standings range and the bingo cutoff key off
             one — so the form starts with a sensible one instead of asking
             for something nobody has an opinion about yet. -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <u-form-field
                :label="$t('admin.start_date')"
                :description="$t('admin.start_date_desc')"
                :error="form.errors.start_date"
                required
            >
                <u-input v-model="form.start_date" type="date" class="w-full" />
            </u-form-field>

            <u-form-field
                :label="$t('admin.end_date')"
                :error="form.errors.end_date"
                :description="$t('admin.end_date_desc')"
                required
            >
                <!-- min, not just a validation rule: the picker itself stops
                     offering a day before the start, so the invalid choice is
                     never on screen to be made. -->
                <u-input v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full" />
            </u-form-field>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    // The parent's useForm instance. Passed as a shared store rather than
    // decomposed into a dozen value props with matching emits — every field
    // here writes to it, and threading each one back up would be more
    // plumbing than markup.
    form: { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
    blueprints: { type: Array, default: () => [] },
});

const emit = defineEmits(['search-blueprints', 'apply-blueprint']);

// Title is the one field the parent needs to react to (it drives the
// blueprint lookup), so it goes through an explicit handler rather than
// v-model.
function onTitle(value) {
    props.form.title = value;
    emit('search-blueprints', value);
}
</script>
