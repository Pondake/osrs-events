import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Head: { props: ['title'], template: '<div><slot /></div>' },
    usePage: () => ({ url: '/events/abc?tab=1', props: { ziggy: { url: 'https://osrs-events.com' } } }),
}));

const EventSeoHead = (await import('@/Components/EventSeoHead.vue')).default;

const meta = (wrapper, name) => wrapper.find(`meta[name="${name}"]`).attributes('content');

const event = (overrides = {}) => ({
    id: 'abc',
    title: 'Clan bingo',
    type: 'BINGO',
    description: null,
    is_listed: true,
    ...overrides,
});

/**
 * Google Search Console reported a public event as "excluded by noindex":
 * every event page hardcoded `noindex, nofollow` (or carried no robots tag
 * and no canonical at all), while listed events open for anyone.
 */
describe('EventSeoHead', () => {
    it('lets a listed event be indexed, with a canonical', () => {
        const wrapper = mount(EventSeoHead, { props: { event: event() } });

        expect(meta(wrapper, 'robots')).toBe('index, follow');
        expect(wrapper.find('link[rel="canonical"]').attributes('href')).toBe('https://osrs-events.com/events/abc');
    });

    it('keeps an unlisted event out of the index', () => {
        const wrapper = mount(EventSeoHead, { props: { event: event({ is_listed: false }) } });

        expect(meta(wrapper, 'robots')).toBe('noindex, follow');
    });

    it('describes the event with its own text, as plain text', () => {
        const wrapper = mount(EventSeoHead, {
            props: { event: event({ description: 'Join **us** on [Discord](https://discord.gg/x)' }) },
        });

        expect(meta(wrapper, 'description')).toBe('Join us on Discord');
    });

    it('falls back to a description per event type', () => {
        const wrapper = mount(EventSeoHead, { props: { event: event({ type: 'SKILL_RACE' }) } });

        expect(meta(wrapper, 'description')).toBe('t:seo.skill_race_event_desc');
    });

    it('cuts a long description to a snippet length', () => {
        const wrapper = mount(EventSeoHead, { props: { event: event({ description: 'x'.repeat(400) }) } });

        expect(meta(wrapper, 'description')).toHaveLength(160);
    });
});
