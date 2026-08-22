import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Subscribes to an event's live channel.
 *
 * Extracted once a second page needed it — the connection handling is
 * identical for every event type and only the message name and what to do
 * with the payload differ. Three copies of the reconnect-and-staleness logic
 * would be three places to get it subtly wrong.
 *
 * EventSource rather than a WebSocket: nothing on these pages sends anything
 * back, so a return channel would be a second service to run for no gain. The
 * browser reconnects on its own, including after the server closes the stream
 * on its own timer — see EventStreamController.
 *
 * @param {object} options
 * @param {() => string|null} options.url        where to connect, or null not to
 * @param {string} options.event                 the SSE event name to listen for
 * @param {(payload: object) => void} options.onMessage
 */
export function useEventStream({ url, event, onMessage }) {
    // Whether a channel was opened at all (it isn't for a finished event, or
    // where EventSource doesn't exist), and whether it has stopped keeping up.
    const streaming = ref(false);
    const stale = ref(false);

    let source = null;
    let staleTimer = null;
    let target = null;

    // The server ends every stream after ~45 seconds by design, so a
    // disconnect is the normal case rather than a fault. Flipping the
    // indicator the instant one happens would report a problem roughly every
    // 45 seconds while the page is working perfectly — so a disconnect only
    // counts once a reconnect has failed to land within this window.
    const STALE_AFTER_MS = 6000;

    function markLive() {
        clearTimeout(staleTimer);
        staleTimer = null;
        stale.value = false;
    }

    function connect() {
        if (source || !target) return;

        streaming.value = true;
        source = new EventSource(target);

        source.addEventListener('open', markLive);

        source.addEventListener(event, (message) => {
            try {
                onMessage(JSON.parse(message.data));
                markLive();
            } catch (error) {
                console.error(error);
            }
        });

        // Fires on every disconnect, the scheduled one included. EventSource
        // reconnects by itself, so this starts a grace period rather than
        // tearing anything down.
        source.addEventListener('error', (error) => {
            // CLOSED means EventSource has given up and will not retry —
            // the only case here that is actually a fault. CONNECTING is the
            // scheduled 45-second turnover reconnecting, and logging that
            // put an error in the console every 45 seconds of a healthy
            // page, which is how a real failure gets missed.
            if (source?.readyState === EventSource.CLOSED) {
                console.error(error);
            }

            if (staleTimer === null) {
                staleTimer = setTimeout(() => {
                    stale.value = true;
                    // The reconnect never landed. Logged once per stall
                    // rather than once per disconnect.
                    console.error('Event stream went stale', { url: target, event });
                }, STALE_AFTER_MS);
            }
        });
    }

    function disconnect() {
        // Without this the browser keeps reconnecting to a page nobody is on,
        // and every reconnect takes a PHP worker for 45 seconds.
        clearTimeout(staleTimer);
        staleTimer = null;
        source?.close();
        source = null;

        // And the page stops claiming to be live, because it is not. The
        // indicator said "Updating live" on a backgrounded tab for as long
        // as this method existed — which is the one state where it is
        // definitely wrong, and the state a tab spends most of its life in.
        streaming.value = false;
    }

    /**
     * A backgrounded tab drops its connection and picks it up again on
     * return.
     *
     * A held stream is not free at either end. The server keeps a PHP worker
     * for the life of it, so a viewer who left the tab open yesterday costs
     * exactly as much as one watching. And a browser will only hold so many
     * connections to one origin — measured here, a second stream to the same
     * origin does not connect at all while the first is open, and ordinary
     * requests from that tab stall behind it. Someone with the site open in
     * three tabs was starving the two they were not looking at.
     *
     * Nothing is missed by dropping it: the channel sends a full snapshot
     * rather than a diff, and the reconnect carries no Last-Event-ID, so the
     * first thing a returning tab gets is current state.
     */
    function onVisibilityChange() {
        if (document.visibilityState === 'hidden') {
            disconnect();
            // Not stale — deliberately disconnected. Saying otherwise would
            // put a warning on a tab that is working exactly as intended.
            stale.value = false;

            return;
        }

        connect();
    }

    onMounted(() => {
        // Guarded: SSR has no EventSource, and neither do a few old browsers.
        // The page is already fully rendered without it, so there is nothing
        // to fall back to — it just stops updating by itself, and the
        // indicator stays hidden rather than claiming to be live.
        if (typeof window === 'undefined' || !('EventSource' in window)) return;

        target = url();
        if (!target) return;

        document.addEventListener('visibilitychange', onVisibilityChange);

        // A tab restored from the background starts hidden, so this is not
        // always true at mount.
        if (document.visibilityState !== 'hidden') connect();
    });

    onBeforeUnmount(() => {
        if (typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', onVisibilityChange);
        }

        disconnect();
    });

    return { streaming, stale };
}
