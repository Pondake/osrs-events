<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? $t('admin.edit_supporter') : $t('admin.create_supporter')" :dismissible="false">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('admin.supporter_name_label')" :error="form.errors.name" required>
                    <u-input v-model="form.name" class="w-full" maxlength="80" />
                </u-form-field>

                <u-form-field :label="$t('admin.supporter_roles_label')" :error="form.errors.roles" required>
                    <div class="flex flex-col gap-1">
                        <u-checkbox
                            v-for="role in roles"
                            :key="role"
                            :model-value="form.roles.includes(role)"
                            :label="$t(`supporters.role_${role}`)"
                            class="max-sm:min-h-11 items-center"
                            @update:model-value="(checked) => toggleRole(role, checked)"
                        />
                    </div>
                </u-form-field>

                <u-form-field :label="$t('admin.supporter_link_label')" :help="$t('admin.supporter_link_help')" :error="form.errors.link">
                    <u-input v-model="form.link" class="w-full" maxlength="255" />
                </u-form-field>

                <u-form-field :label="$t('admin.supporter_sort_label')" :help="$t('admin.supporter_sort_help')" :error="form.errors.sort_order">
                    <u-input v-model.number="form.sort_order" type="number" class="w-32" />
                </u-form-field>

                <u-form-field :help="$t('admin.supporter_consented_help')">
                    <u-switch v-model="form.consented" :label="$t('admin.supporter_consented_label')" class="max-sm:min-h-11" />
                </u-form-field>

                <u-form-field :help="$t('admin.supporter_visible_help')">
                    <u-switch v-model="form.is_visible" :label="$t('admin.supporter_visible_label')" class="max-sm:min-h-11" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :label="$t('common.save')" :loading="form.processing" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
    supporter: { type: Object, default: null },
    roles: { type: Array, required: true },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });
const isEdit = computed(() => props.supporter !== null);

// Consent starts off: a new row is never published until someone ticks it.
const blank = () => ({ name: '', roles: [], link: '', sort_order: 0, consented: false, is_visible: true });

const form = useForm(blank());

// On every open, so a second "Add" starts empty rather than from the last save.
watch(
    () => [props.open, props.supporter],
    ([open, supporter]) => {
        if (!open) return;
        form.defaults(
            supporter
                ? {
                      name: supporter.name,
                      roles: [...supporter.roles],
                      link: supporter.link ?? '',
                      sort_order: supporter.sort_order,
                      consented: supporter.consented,
                      is_visible: supporter.is_visible,
                  }
                : blank(),
        ).reset();
        form.clearErrors();
    },
    { immediate: true },
);

function toggleRole(role, checked) {
    form.roles = checked ? [...form.roles, role] : form.roles.filter((r) => r !== role);
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (isOpen.value = false) };

    if (isEdit.value) {
        form.patch(`/admin/supporters/${props.supporter.id}`, options);
    } else {
        form.post('/admin/supporters', options);
    }
}
</script>
