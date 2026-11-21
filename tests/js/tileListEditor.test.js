import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

const router = { post: vi.fn(), patch: vi.fn(), delete: vi.fn() };

vi.mock('@inertiajs/vue3', () => ({
    router,
    usePage: () => ({ props: { site: { isLocal: false } } }),
}));

const TileListEditor = (await import('@/Components/TileListEditor.vue')).default;

const stubs = {
    'u-modal': { template: '<div><slot name="body" /><slot name="footer" /></div>' },
    'u-form-field': { template: '<div><slot /></div>' },
    'u-input': { template: '<input />' },
    'u-switch': { template: '<span />' },
    'u-select': { template: '<span />' },
    'u-progress': { template: '<span />' },
    'u-alert': { template: '<span />' },
    'u-icon': { template: '<span />' },
    'u-badge': { props: ['label'], template: '<span class="badge">{{ label }}</span>' },
    'u-button': { props: ['label'], emits: ['click'], template: '<button class="btn" @click="$emit(\'click\')">{{ label }}</button>' },
    'task-picker': {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<span class="picker">{{ modelValue?.title ?? "none" }}</span>',
    },
};

function editor() {
    return mount(TileListEditor, {
        props: { open: true, eventId: 'event-1', type: 'SNAKES_LADDERS', items: [], total: 25 },
        global: {
            stubs,
            mocks: { $t: (key, replacements = {}) => `t:${key}${Object.values(replacements).join(':')}` },
        },
    });
}

const rowButton = (wrapper, n) => wrapper.findAll('button').find((b) => b.text().startsWith(String(n)));
const button = (wrapper, label) => wrapper.findAll('button.btn').find((b) => b.text() === label);

async function pickTask(wrapper, task) {
    wrapper.findComponent(stubs['task-picker']).vm.$emit('update:modelValue', task);
    await nextTick();
}

describe('TileListEditor', () => {
    it('keeps a picked task when the row is collapsed and opened again', async () => {
        const wrapper = editor();

        await rowButton(wrapper, 3).trigger('click');
        await pickTask(wrapper, { id: 'task-1', title: 'Kill Zulrah' });
        await rowButton(wrapper, 3).trigger('click');

        expect(wrapper.find('.picker').exists()).toBe(false);
        expect(wrapper.text()).toContain('t:tile_list.unsaved');

        await rowButton(wrapper, 3).trigger('click');

        expect(wrapper.find('.picker').text()).toBe('Kill Zulrah');
    });

    it('keeps it while another row is open, and sends the right one on save', async () => {
        const wrapper = editor();

        await rowButton(wrapper, 3).trigger('click');
        await pickTask(wrapper, { id: 'task-1', title: 'Kill Zulrah' });
        await rowButton(wrapper, 4).trigger('click');

        expect(wrapper.find('.picker').text()).toBe('none');

        await rowButton(wrapper, 3).trigger('click');
        await button(wrapper, 't:common.save').trigger('click');

        expect(router.post).toHaveBeenCalledWith(
            '/events/event-1/tiles',
            expect.objectContaining({ position: 2, task_id: 'task-1' }),
            expect.any(Object),
        );
    });

    it('throws the change away on cancel, and only then', async () => {
        const wrapper = editor();

        await rowButton(wrapper, 3).trigger('click');
        await pickTask(wrapper, { id: 'task-1', title: 'Kill Zulrah' });
        await button(wrapper, 't:common.cancel').trigger('click');

        expect(wrapper.text()).not.toContain('t:tile_list.unsaved');

        await rowButton(wrapper, 3).trigger('click');

        expect(wrapper.find('.picker').text()).toBe('none');
    });

    it('does not call an untouched row unsaved', async () => {
        const wrapper = editor();

        await rowButton(wrapper, 3).trigger('click');
        await rowButton(wrapper, 3).trigger('click');

        expect(wrapper.text()).not.toContain('t:tile_list.unsaved');
    });
});
