<template>
    <div class="space-y-4 py-2">
        <!-- Templates used to be suggested here, as a dropdown under the
             title. They moved to a step of their own (TemplateFields) once a
             template started carrying the whole shape of an event: a one-line
             suggestion cannot show a grid size or a win condition, and
             picking a format you cannot see is picking a name.

             So this is an ordinary title field again. -->
        <u-form-field :label="$t('admin.board_title')" :error="form.errors.title" required>
            <u-input v-model="form.title" class="w-full" :placeholder="$t('admin.board_title_placeholder')" />
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
defineProps({
    // The parent's useForm instance. Passed as a shared store rather than
    // decomposed into a dozen value props with matching emits — every field
    // here writes to it, and threading each one back up would be more
    // plumbing than markup.
    form: { type: Object, required: true },
    // Unused here now that templates have their own step, but still passed by
    // the edit tabs and worth declaring rather than warning about.
    isEdit: { type: Boolean, default: false },
});
</script>
