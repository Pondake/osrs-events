import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

const StandingHereList = (await import('@/Components/StandingHereList.vue')).default;

function list(props) {
    return mount(StandingHereList, {
        props,
        global: {
            stubs: { 'u-avatar': { template: '<span class="avatar" />' } },
            mocks: {
                $t: (key, replacements = {}) => `t:${key}${Object.values(replacements).join(':')}`,
                $tChoice: (key, count) => `t:${key}:${count}`,
            },
        },
    });
}

const players = (count) => Array.from({ length: count }, (_, i) => ({ id: `p${i}`, name: `Player ${i}`, avatarUrl: null, isYou: i === 0 }));

describe('StandingHereList', () => {
    it('renders nothing on an empty tile', () => {
        expect(list({ players: [] }).text()).toBe('');
    });

    it('shows the first eight of twenty and opens the rest on request', async () => {
        const wrapper = list({ players: players(20) });

        expect(wrapper.findAll('.avatar')).toHaveLength(8);
        expect(wrapper.text()).toContain('t:board.detail_more12');

        await wrapper.find('button').trigger('click');

        expect(wrapper.findAll('.avatar')).toHaveLength(20);
        expect(wrapper.text()).toContain('Player 19');
    });

    it('marks your own row', () => {
        expect(list({ players: players(2) }).text()).toContain('t:board.detail_you');
    });

    it('collapses to a count when names are withheld', () => {
        const wrapper = list({ players: players(5), named: false });

        expect(wrapper.text()).toContain('t:board.detail_standing_count:5');
        expect(wrapper.findAll('li')).toHaveLength(0);
    });
});
