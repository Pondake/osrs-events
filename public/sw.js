/**
 * OSRS Events service worker.
 *
 * Two jobs, and deliberately no third: receive pushes, and route the tap.
 *
 * There is no offline caching here on purpose. Almost every page in this app
 * is live data — standings that move, a review queue that empties, a board
 * somebody else is playing — and a cached shell that renders yesterday's
 * numbers with no indication they are stale is worse than a page that plainly
 * fails to load.
 */

const FALLBACK_TITLE = 'OSRS Events';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

/**
 * Present but intentionally inert.
 *
 * Chrome's installability criteria have historically included "a service
 * worker with a fetch handler". Whether that is still enforced is not
 * something worth betting the install prompt on, and a pass-through handler
 * costs nothing — so it exists, does nothing, and is not a caching strategy
 * in disguise.
 */
self.addEventListener('fetch', () => {});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        // A push whose body will not parse still deserves a notification.
        // Failing to show one after a push has woken the worker makes some
        // browsers substitute a generic "this site was updated in the
        // background" notice — or, repeatedly, revoke the subscription for
        // abuse. So take whatever text there was and show that.
        console.error(error);
        payload = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || FALLBACK_TITLE, {
            body: payload.body || '',
            icon: '/android-chrome-192x192.png',
            badge: '/favicon-32x32.png',
            // Same tag replaces rather than stacks. Ten claims reviewed in a
            // minute should be one line on a lock screen, not ten — and
            // renotify makes the replacement still buzz, so a collapsed
            // update is not a silent one.
            tag: payload.tag || payload.category || 'osrs-events',
            renotify: Boolean(payload.tag || payload.category),
            data: {
                path: typeof payload.path === 'string' ? payload.path : '/',
                category: payload.category || null,
            },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const path = (event.notification.data && event.notification.data.path) || '/';
    // Resolved against the worker's own scope, never against anything the
    // server sent. The payload carries a path precisely so that the origin is
    // decided here, where it is known to be right.
    const target = new URL(path, self.location.origin);

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (new URL(client.url).origin !== self.location.origin) continue;

                return client
                    .navigate(target.href)
                    .then((navigated) => (navigated || client).focus())
                    .catch((error) => {
                        // navigate() is unavailable on some platforms and
                        // rejects outright for clients this worker does not
                        // control. The running app listens for this message
                        // and routes itself, which is also faster than a
                        // reload since it is a client-side transition.
                        console.error(error);
                        client.postMessage({ type: 'app:navigate', path: target.pathname + target.search });

                        return client.focus();
                    });
            }

            return self.clients.openWindow(target.href);
        }),
    );
});

/**
 * Browsers rotate subscriptions on their own schedule, without asking.
 *
 * Without this the old endpoint quietly stops delivering while the server
 * keeps posting to it and sees no error at all — a device that simply goes
 * silent for good.
 *
 * The re-subscribe happens here, but the **server is not told from here**.
 * Telling it would need an endpoint the worker can call, and the worker
 * holds no session or token, so that endpoint could not be authenticated —
 * anyone holding a stolen endpoint URL could point a person's notifications
 * at a subscription of their own. Instead the browser is left holding a
 * valid subscription, and `usePush`'s per-load sync registers it the next
 * time the app is opened. The cost is real and worth stating: between the
 * rotation and that next visit, this device receives nothing. The stale row
 * on the server cleans itself up when the push service answers 410.
 */
self.addEventListener('pushsubscriptionchange', (event) => {
    const old = event.oldSubscription;
    const key = old && old.options ? old.options.applicationServerKey : null;

    if (!key) return;

    event.waitUntil(
        self.registration.pushManager
            .subscribe({ userVisibleOnly: true, applicationServerKey: key })
            .catch((error) => console.error(error)),
    );
});
