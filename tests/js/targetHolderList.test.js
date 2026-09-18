import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

const TargetHolderList = (await import('@/Components/TargetHolderList.vue')).default;

/**
 * Who has a square or tile, in the detail dialog.
 *
 * Two rules here are worth holding down, and neither is visible from the
 * server side that feeds the list:
 *
 * - A list where every name was withheld is a wall of identical rows. It
 *   collapses to a count, and your own row survives the collapse — the one
 *   identity on an anonymised list a reader is entitled to is theirs.
 * - A popular square on a big event is a scroll, not a list.
 *
 * The anonymising itself is the server's job and has its own tests; what is
 * checked here is what the dialog does with the result.
 */
const stubs = {
    'u-avatar': { template: '<span class="avatar" />' },
    'u-badge': { props: ['label'], template: '<span class="badge">{{ label }}</span>' },
    'u-skeleton': { template: '<span class="skeleton" />' },
    ClaimSourceBadge: true,
};

function list(props) {
    return mount(TargetHolderList, {
        props: { holders: [], inProgress: [], requiredCount: 1, ...props },
        global: {
            stubs,
            mocks: {
                $t: (key, replacements = {}) => `t:${key}${Object.values(replacements).join(':')}`,
                $tChoice: (key, count) => `t:${key}:${count}`,
            },
        },
    });
}

const anonymous = (count) => Array.from({ length: count }, () => ({ name: null, avatarUrl: null, isYou: false }));

describe('TargetHolderList', () => {
    it('names the people who have it when their names are public', () => {
        const wrapper = list({ holders: [{ name: 'Pondake', avatarUrl: null, isYou: false }] });

        expect(wrapper.text()).toContain('Pondake');
    });

    it('collapses a list where every name was withheld into a count', () => {
        const wrapper = list({ holders: anonymous(9) });

        expect(wrapper.text()).toContain('t:board.detail_holder_count:9');
        // Nine rows saying the same nothing is the thing being avoided.
        expect(wrapper.findAll('li')).toHaveLength(0);
    });

    it('keeps your own row when the rest of the list is withheld', () => {
        const wrapper = list({ holders: [...anonymous(3), { name: null, avatarUrl: null, isYou: true }] });

        expect(wrapper.text()).toContain('t:board.detail_you');
        // Three others, not four: you are not one of them.
        expect(wrapper.text()).toContain('t:board.detail_holder_count:3');
    });

    it('stops listing after six and counts the rest', () => {
        const holders = Array.from({ length: 10 }, (_, i) => ({ name: `Player ${i}`, avatarUrl: null, isYou: false }));
        const wrapper = list({ holders });

        expect(wrapper.text()).toContain('Player 5');
        expect(wrapper.text()).not.toContain('Player 6');
        expect(wrapper.text()).toContain('t:board.detail_more4');
    });

    it('has no "on the way" section on a target that is claimed by the first report', () => {
        const wrapper = list({
            holders: [],
            inProgress: [{ name: 'Pondake', avatarUrl: null, isYou: false, done: 2 }],
            requiredCount: 1,
        });

        expect(wrapper.text()).not.toContain('t:board.detail_in_progress');
    });

    it('shows how far everyone else has got on a counted target', () => {
        const wrapper = list({
            inProgress: [{ name: 'Pondake', avatarUrl: null, isYou: false, done: 3 }],
            requiredCount: 5,
        });

        expect(wrapper.text()).toContain('t:board.detail_in_progress');
        expect(wrapper.text()).toContain('t:common.progress_badge3:5');
    });
});
