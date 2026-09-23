import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { characterNames, characterRows } from '@/Support/osrsCharacters';

const user = ref({ osrsCharacters: ['Pondake', 'Iron Pondake'] });

vi.mock('@/Composables/useAuth', () => ({ useAuth: () => ({ user }) }));

const OsrsCharactersField = (await import('@/Components/OsrsCharactersField.vue')).default;
const ClaimCharacterField = (await import('@/Components/ClaimCharacterField.vue')).default;

const mocks = { $t: (key) => `t:${key}` };

function field(names, props = {}) {
    return mount(OsrsCharactersField, {
        props: { modelValue: characterRows(names), max: 3, ...props },
        global: {
            mocks,
            stubs: {
                OsrsUsernameField: { props: ['modelValue'], template: '<input :value="modelValue" />' },
                'u-badge': { props: ['label'], template: '<span class="badge">{{ label }}</span>' },
                'u-icon': true,
                'u-button': { props: ['label', 'ariaLabel'], emits: ['click'], template: '<button :aria-label="ariaLabel" @click="$emit(\'click\')">{{ label }}</button>' },
            },
        },
    });
}

const emitted = (wrapper) => characterNames(wrapper.emitted('update:modelValue').at(-1)[0]);

describe('OsrsCharactersField', () => {
    it('calls the top name the main and the rest alts', () => {
        const badges = field(['Pondake', 'Iron Pondake']).findAll('.badge').map((badge) => badge.text());

        expect(badges).toEqual(['t:profile.osrs_main', 't:profile.osrs_alt']);
    });

    it('moves an alt up, which makes it the main from the second row', async () => {
        const wrapper = field(['Pondake', 'Iron Pondake']);

        await wrapper.find('button[aria-label="t:profile.osrs_character_up"]').trigger('click');

        expect(emitted(wrapper)).toEqual(['Iron Pondake', 'Pondake']);
    });

    it('removes a row', async () => {
        const wrapper = field(['Pondake', 'Iron Pondake']);

        await wrapper.findAll('button[aria-label="t:profile.osrs_character_remove"]')[0].trigger('click');

        expect(emitted(wrapper)).toEqual(['Iron Pondake']);
    });

    it('stops offering another row at the limit', () => {
        expect(field(['A', 'B']).text()).toContain('t:profile.osrs_add_alt');
        expect(field(['A', 'B', 'C']).text()).not.toContain('t:profile.osrs_add_alt');
    });

    it('drops blank rows from what is sent', () => {
        expect(characterNames([...characterRows(['Pondake']), ...characterRows([' '])])).toEqual(['Pondake']);
    });
});

describe('ClaimCharacterField', () => {
    const claim = (allowAlts) => mount(ClaimCharacterField, {
        props: { allowAlts },
        global: {
            mocks,
            stubs: {
                'u-form-field': { template: '<div class="field"><slot /></div>' },
                'u-select': { props: ['items', 'modelValue'], template: '<select :data-value="modelValue"><option v-for="i in items" :key="i.value">{{ i.value }}</option></select>' },
            },
        },
    });

    it('asks which character when alts count', () => {
        const wrapper = claim(true);

        expect(wrapper.findAll('option').map((option) => option.text())).toEqual(['Pondake', 'Iron Pondake']);
        expect(wrapper.find('select').attributes('data-value')).toBe('Pondake');
    });

    it('does not ask when the event counts mains only', () => {
        expect(claim(false).find('.field').exists()).toBe(false);
    });
});
