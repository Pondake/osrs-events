import { ref, computed } from 'vue';

// The ask policy — how often, and after how long — lives in Support so the
// same file answers it for the in-app offer bar and for the automatic ask,
// and so both are testable without a browser.
import { ASK_KEY, mayAskAutomatically, recordAutomaticAsk } from '@/Support/pushPrompt';

/**
 * Web Push, from the browser's side.
 *
 * Push is unusual in how many different things "it doesn't work" can mean,
 * and in how few of them raise an error anybody sees. So the main product of
 * this composable is not the subscription — it is `reason`: a single string
 * naming which of the eight states this browser is actually in, so the
 * settings page can say it in words instead of showing a dead toggle.
 *
 * State is module-level rather than per-caller. Two components use this at
 * once (AppRoot on every page, the settings page when open) and they must not
 * each run their own subscribe against the same browser.
 */

const REASON = {
    READY: 'ready',
    SUBSCRIBED: 'subscribed',
    /** No Push API at all — an old browser, or a webview. */
    UNSUPPORTED: 'unsupported',
    /** Push requires a secure context; plain http never gets one. */
    INSECURE: 'insecure',
    /** iOS grants notifications only to a PWA added to the home screen. */
    IOS_NEEDS_INSTALL: 'ios_needs_install',
    /** The user said no at the OS level. Nothing here can undo that. */
    BLOCKED: 'blocked',
    /** The user switched it off in this app. Different from BLOCKED. */
    OPTED_OUT: 'opted_out',
    /** The server has no VAPID keys — nothing can be sent to anyone. */
    SERVER_UNCONFIGURED: 'server_unconfigured',
};

const supported = ref(false);
const permission = ref('default');
const subscribed = ref(false);
const busy = ref(false);
const serverConfigured = ref(true);
const optedOut = ref(false);
const ready = ref(false);

/**
 * The automatic attempt has run to a conclusion.
 *
 * Separate from `ready`, which only means "we have read the browser's state".
 * The in-app offer waits on this: on Chromium autoSubscribe may raise the
 * real permission dialog, and showing a bar asking the same question
 * underneath it is asking twice at once.
 */
const settled = ref(false);

/**
 * iOS is the one platform where asking at the wrong moment is destructive:
 * `requestPermission()` outside a user gesture does not merely fail there, it
 * records a denial the page can never undo. So iOS is detected and left
 * entirely to the explicit toggle.
 *
 * iPadOS reports itself as a Mac, hence the touch-point test — a Mac has no
 * touch points, an iPad claims five.
 */
function isIos() {
    if (typeof navigator === 'undefined') return false;

    return (
        /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    );
}

function isStandalone() {
    if (typeof window === 'undefined') return false;

    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

/**
 * Laravel's CSRF cookie, for the endpoints called with fetch instead of an
 * Inertia visit. Inertia sets this header for its own requests; a bare fetch
 * has to do it by hand or every POST here is a 419.
 */
function csrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function post(url, body, method = 'POST') {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`${method} ${url} failed with ${response.status}`);
    }

    return response.status === 204 ? null : response.json();
}

/** VAPID keys travel as base64url; PushManager wants raw bytes. */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);

    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}

async function registration() {
    return navigator.serviceWorker.register('/sw.js', { scope: '/' });
}

/** Hand the browser's subscription to the server. Always an upsert there. */
async function sync(subscription) {
    const json = subscription.toJSON();

    await post('/push/subscriptions', {
        endpoint: json.endpoint,
        keys: json.keys,
        // aes128gcm where the browser supports it, aesgcm otherwise. The
        // server stores whichever, because the encryption has to match what
        // this specific browser can decrypt.
        contentEncoding:
            typeof PushManager !== 'undefined' && PushManager.supportedContentEncodings
                ? PushManager.supportedContentEncodings[0]
                : 'aesgcm',
    });
}

async function createSubscription() {
    const { key, configured } = await fetch('/push/public-key', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    }).then((response) => response.json());

    serverConfigured.value = Boolean(configured);

    if (!configured) {
        throw new Error('The server has no VAPID keys configured.');
    }

    const worker = await registration();

    return worker.pushManager.subscribe({
        // Non-negotiable in every browser: a push must result in something
        // the user can see. Silent pushes are not a thing the platform allows.
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(key),
    });
}

export function usePush() {
    /**
     * Read where this browser actually stands. Cheap, and safe to call on
     * every page — it touches no network unless a subscription exists.
     */
    async function refresh() {
        if (typeof window === 'undefined' || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            supported.value = false;
            ready.value = true;

            return;
        }

        supported.value = true;
        permission.value = Notification.permission;

        try {
            const worker = await registration();
            const existing = await worker.pushManager.getSubscription();
            subscribed.value = existing !== null;
        } catch (error) {
            console.error(error);
            subscribed.value = false;
        }

        ready.value = true;
    }

    /**
     * The silent half. Runs on every page load for a signed-in user and is
     * deliberately quiet about everything except the one case where asking is
     * both safe and wanted.
     *
     * The branch that matters most is the second one: permission granted and
     * a subscription already in the browser still re-posts it. That is an
     * upsert, so it costs almost nothing — and it silently heals the state
     * where the browser holds a subscription the server has lost (a wiped
     * database, a pruned row). Nothing else can detect that state: the toggle
     * reads "on" and not a single notification arrives.
     */
    async function autoSubscribe() {
        if (!supported.value || optedOut.value || busy.value) {
            settled.value = true;

            return;
        }

        // Denied is final, and re-asking is not even possible — the browser
        // ignores the call. Nagging is what makes people uninstall.
        if (permission.value === 'denied') {
            settled.value = true;

            return;
        }

        try {
            if (permission.value === 'granted') {
                const worker = await registration();
                const existing = await worker.pushManager.getSubscription();

                await sync(existing ?? (await createSubscription()));
                subscribed.value = true;

                return;
            }

            // From here on the automatic path may or may not produce a
            // prompt, and on Firefox and Safari it definitely will not: both
            // require a user gesture and ignore the call otherwise. Chrome
            // may also answer with its quiet UI — a bell in the address bar,
            // indistinguishable from nothing having happened. That is what
            // the in-app offer bar is for; see Support/pushPrompt.js.

            // Undecided. Chrome and Android show their own accept/deny prompt
            // here, which is exactly the intended experience — the OS asks
            // once and honours the answer forever after.
            //
            // iOS is skipped: there, requestPermission() outside a user
            // gesture records a denial that cannot be reversed from the page,
            // so the toggle on the settings page is the only safe way in.
            if (isIos()) return;

            // Not on every page load — a permission prompt that keeps
            // returning is a reason to leave — but not once-ever either, for
            // the reason above RETRY_AFTER_MS.
            if (!mayAskAutomatically(window.localStorage)) return;

            recordAutomaticAsk(window.localStorage);

            const result = await Notification.requestPermission();
            permission.value = result;

            if (result !== 'granted') return;

            await sync(await createSubscription());
            subscribed.value = true;
        } catch (error) {
            // Never surfaced. This runs unasked in the background; a toast
            // about a feature nobody just tried to use reads as a broken app.
            console.error(error);
        } finally {
            settled.value = true;
        }
    }

    /**
     * Forget that the automatic ask already happened.
     *
     * Needed because the once-ever flag is set *before* the call, so a prompt
     * the browser silently refused to show still spends it. Without a way to
     * clear it, a browser that quietly suppressed the one attempt could never
     * be asked again from this app — which is the exact failure being
     * reported when somebody says "it never prompted me".
     */
    function clearPromptMemory() {
        try {
            window.localStorage.removeItem(ASK_KEY);
        } catch (error) {
            console.error(error);
        }
    }

    /**
     * The explicit switch-on, from a user gesture. This is the only path iOS
     * ever takes, and the only one whose failure is worth showing — here
     * somebody did just press something, so silence would be the bug.
     */
    async function enable() {
        busy.value = true;

        try {
            const result = await Notification.requestPermission();
            permission.value = result;

            if (result !== 'granted') return false;

            await sync(await createSubscription());
            subscribed.value = true;
            optedOut.value = false;

            return true;
        } catch (error) {
            console.error(error);

            return false;
        } finally {
            busy.value = false;
        }
    }

    /**
     * Off means off, and has to be recorded as such.
     *
     * Dropping the browser subscription leaves the OS permission granted —
     * which is precisely the state autoSubscribe reads as "granted, so
     * subscribe silently". The server stores the opt-out; without it this
     * button would undo itself on the very next page load.
     */
    async function disable() {
        busy.value = true;

        try {
            const worker = await registration();
            const existing = await worker.pushManager.getSubscription();

            await post('/push/subscriptions', { endpoint: existing?.endpoint ?? null }, 'DELETE');

            if (existing) await existing.unsubscribe();

            subscribed.value = false;
            optedOut.value = true;

            return true;
        } catch (error) {
            console.error(error);

            return false;
        } finally {
            busy.value = false;
        }
    }

    /**
     * Which state this browser is in — the whole point of the composable.
     * Ordered most-blocking first: there is no use telling somebody they are
     * opted out when the server could not send to anyone regardless.
     */
    const reason = computed(() => {
        if (!supported.value) {
            return typeof window !== 'undefined' && !window.isSecureContext ? REASON.INSECURE : REASON.UNSUPPORTED;
        }

        if (!serverConfigured.value) return REASON.SERVER_UNCONFIGURED;
        if (isIos() && !isStandalone()) return REASON.IOS_NEEDS_INSTALL;
        if (permission.value === 'denied') return REASON.BLOCKED;
        if (optedOut.value) return REASON.OPTED_OUT;

        return subscribed.value ? REASON.SUBSCRIBED : REASON.READY;
    });

    /** Seed the two facts only the server knows, from page props. */
    function hydrate({ serverConfigured: configured, optedOut: out }) {
        if (configured !== undefined) serverConfigured.value = Boolean(configured);
        if (out !== undefined) optedOut.value = Boolean(out);
    }

    return {
        REASON,
        supported,
        permission,
        subscribed,
        busy,
        ready,
        settled,
        // Exposed rather than derived from `reason`: that computed checks
        // iOS and blocking first, so reading an opt-out out of it depends on
        // an ordering that has nothing to do with the question.
        optedOut,
        reason,
        refresh,
        autoSubscribe,
        enable,
        disable,
        hydrate,
        clearPromptMemory,
        isIos,
        isStandalone,
    };
}
