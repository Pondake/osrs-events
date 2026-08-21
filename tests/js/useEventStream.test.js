import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick } from 'vue';
import { mount } from '@vue/test-utils';

import { useEventStream } from '@/Composables/useEventStream';

/**
 * The live channel's connection handling.
 *
 * Worth testing away from a page, because what it manages is invisible when
 * it works and expensive when it does not. Every open connection holds a PHP
 * worker for its whole life, and a browser will only hold so many to one
 * origin — measured against the dev server, a SECOND stream to the same
 * origin does not connect at all while the first is open, and ordinary
 * requests from that tab queue behind it. So "did we actually let go of the
 * connection" is a real question with a real cost attached, and nothing on
 * the page shows the answer either way.
 */

/** Stands in for the browser's EventSource, recording what was done to it. */
class FakeEventSource {
    static instances = [];

    static get live() {
        return FakeEventSource.instances.filter((source) => !source.closed);
    }

    constructor(url) {
        this.url = url;
        this.closed = false;
        this.readyState = 0;
        this.listeners = {};
        FakeEventSource.instances.push(this);
    }

    addEventListener(name, handler) {
        (this.listeners[name] ??= []).push(handler);
    }

    emit(name, payload) {
        (this.listeners[name] ?? []).forEach((handler) => handler(payload));
    }

    close() {
        this.closed = true;
        this.readyState = 2;
    }
}

/** happy-dom's visibilityState is read-only, so it gets replaced outright. */
function setVisibility(state) {
    Object.defineProperty(document, 'visibilityState', { value: state, configurable: true });
    document.dispatchEvent(new Event('visibilitychange'));
}

/**
 * Every mount is tracked and torn down afterwards.
 *
 * The visibility listener lives on the shared `document`, so a component left
 * mounted by one test still answers the visibilitychange the NEXT test fires
 * — and opens a connection while that test is counting them. Left alone this
 * shows up as a wrong number in an unrelated assertion.
 */
const mounted = [];

function mountStream({ url = () => '/events/abc/stream' } = {}) {
    const seen = [];

    const wrapper = mount(defineComponent({
        setup() {
            const stream = useEventStream({
                url,
                event: 'bingo',
                onMessage: (payload) => seen.push(payload),
            });

            // The two refs the page renders, in one readable string.
            return () => h('div', `${stream.streaming.value}/${stream.stale.value}`);
        },
    }));

    mounted.push(wrapper);

    return { wrapper, seen };
}

beforeEach(() => {
    FakeEventSource.instances = [];
    vi.stubGlobal('EventSource', FakeEventSource);
    setVisibility('visible');
    vi.useFakeTimers();
});

afterEach(() => {
    mounted.splice(0).forEach((wrapper) => wrapper.unmount());
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('connecting', () => {
    it('opens one connection to the url it was given', () => {
        mountStream();

        expect(FakeEventSource.live).toHaveLength(1);
        expect(FakeEventSource.live[0].url).toBe('/events/abc/stream');
    });

    /** A finished event has nothing to stream, so it should cost nothing. */
    it('opens nothing when there is no url', () => {
        mountStream({ url: () => null });

        expect(FakeEventSource.instances).toHaveLength(0);
    });

    it('hands parsed payloads to the caller', () => {
        const { seen } = mountStream();

        FakeEventSource.live[0].emit('bingo', { data: JSON.stringify({ squares: 3 }) });

        expect(seen).toEqual([{ squares: 3 }]);
    });

    /** A truncated message must not take the page down with it. */
    it('survives a payload that is not json', () => {
        const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const { seen } = mountStream();

        FakeEventSource.live[0].emit('bingo', { data: '{oh no' });

        expect(seen).toEqual([]);
        expect(spy).toHaveBeenCalled();
        spy.mockRestore();
    });
});

describe('letting go', () => {
    it('closes the connection when the component goes away', () => {
        const { wrapper } = mountStream();
        const source = FakeEventSource.instances[0];

        wrapper.unmount();

        expect(source.closed).toBe(true);
    });

    /**
     * The reason this composable has a visibility branch at all: a tab nobody
     * is looking at was holding a worker on the server and a connection slot
     * in the browser, and the second cost fell on the tab the user WAS
     * looking at.
     */
    it('drops the connection while the tab is in the background', async () => {
        mountStream();

        setVisibility('hidden');
        await nextTick();

        expect(FakeEventSource.live).toHaveLength(0);
    });

    it('picks it up again on return', async () => {
        mountStream();

        setVisibility('hidden');
        await nextTick();
        setVisibility('visible');
        await nextTick();

        expect(FakeEventSource.live).toHaveLength(1);
        expect(FakeEventSource.instances).toHaveLength(2);
    });

    /** Two visible events in a row must not leave a second connection open. */
    it('does not stack connections when already connected', async () => {
        mountStream();

        setVisibility('visible');
        setVisibility('visible');
        await nextTick();

        expect(FakeEventSource.live).toHaveLength(1);
    });

    /**
     * The listener outlives the component otherwise, and reconnects a stream
     * for a page that is no longer on screen.
     */
    it('stops listening for visibility once unmounted', async () => {
        const { wrapper } = mountStream();

        wrapper.unmount();
        setVisibility('hidden');
        setVisibility('visible');
        await nextTick();

        expect(FakeEventSource.live).toHaveLength(0);
    });
});

describe('the staleness indicator', () => {
    /**
     * The server ends every stream on a timer, so a disconnect is the normal
     * case. Reporting it immediately would show trouble on a healthy page
     * roughly every 45 seconds, which is how a real failure gets ignored.
     */
    it('does not report trouble the moment a stream ends', async () => {
        const { wrapper } = mountStream();

        FakeEventSource.live[0].emit('error', new Event('error'));
        await nextTick();

        expect(wrapper.text()).toBe('true/false');
    });

    it('reports trouble once the reconnect fails to land', async () => {
        const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const { wrapper } = mountStream();

        FakeEventSource.live[0].emit('error', new Event('error'));
        vi.advanceTimersByTime(7000);
        await nextTick();

        expect(wrapper.text()).toBe('true/true');
        spy.mockRestore();
    });

    it('clears once a message arrives again', async () => {
        const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const { wrapper } = mountStream();

        FakeEventSource.live[0].emit('error', new Event('error'));
        vi.advanceTimersByTime(7000);
        await nextTick();
        expect(wrapper.text()).toBe('true/true');

        FakeEventSource.live[0].emit('bingo', { data: '{}' });
        await nextTick();

        expect(wrapper.text()).toBe('true/false');
        spy.mockRestore();
    });

    /**
     * A deliberate disconnect is not a fault. Without this the indicator
     * would light up on a tab that was backgrounded on purpose — and be
     * sitting there lit when the user came back.
     */
    it('does not go stale because the tab was backgrounded', async () => {
        const { wrapper } = mountStream();

        setVisibility('hidden');
        vi.advanceTimersByTime(30000);
        await nextTick();

        expect(wrapper.text()).toBe('true/false');
    });
});
