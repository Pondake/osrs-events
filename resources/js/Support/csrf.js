/**
 * The CSRF header for a plain fetch().
 *
 * No <meta name="csrf-token"> exists in app.blade.php, so the XSRF-TOKEN
 * cookie is read instead — the same encrypted-cookie mechanism
 * VerifyCsrfToken accepts, and what Inertia's own client uses under the hood.
 *
 * Returns an empty object rather than a header with an empty token when the
 * cookie is missing: a blank X-XSRF-TOKEN is a 419 either way, and spreading
 * nothing keeps the caller's headers honest.
 */
export function xsrfHeader() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}
