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
        <!-- One field, not two. The old pair of `<input type="date">` boxes
             asked for two decisions where a host is making one, and an end
             before a start was only caught by `min` on the second box (and by
             the server) rather than being unreachable. A range calendar
             cannot express that order at all.

             Both errors are surfaced under the one control: the server still
             validates start_date and end_date separately, so either can come
             back, and hiding one of them because the inputs merged would lose
             a message the host needs. -->
        <u-form-field
            :label="$t('admin.event_window')"
            :description="$t('admin.event_window_desc')"
            :error="form.errors.start_date || form.errors.end_date"
            required
        >
            <event-date-range
                :start="form.start_date"
                :end="form.end_date"
                :has-error="!!(form.errors.start_date || form.errors.end_date)"
                @update:start="form.start_date = $event"
                @update:end="form.end_date = $event"
            />
        </u-form-field>
    </div>
</template>

<script setup>
import EventDateRange from '@/Components/EventDateRange.vue';

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
