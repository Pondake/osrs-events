import { trans } from 'laravel-vue-i18n';

/**
 * Colour and icon per invite status. Same pattern as Support/audit.js: the
 * map lives outside the components so the summary tiles, the filter and the
 * per-row badge can't label the same status three different ways.
 *
 * Keys match InviteController::STATUSES.
 */
export const INVITE_STYLES = {
    active: { color: 'success', icon: 'i-lucide-circle-check' },
    unused: { color: 'neutral', icon: 'i-lucide-circle-dashed' },
    exhausted: { color: 'warning', icon: 'i-lucide-circle-slash' },
    expired: { color: 'error', icon: 'i-lucide-circle-x' },
};

const FALLBACK = { color: 'neutral', icon: 'i-lucide-circle-dot' };

export function inviteStyleFor(status) {
    return INVITE_STYLES[status] ?? FALLBACK;
}

export function inviteStatusLabel(status) {
    return trans(`admin.invite_status_${status}`);
}

export function inviteStatusOptions(statuses) {
    return statuses.map((status) => ({
        value: status,
        label: inviteStatusLabel(status),
        icon: inviteStyleFor(status).icon,
    }));
}

/**
 * "3 of 10" / "3 used" — the second form is not "3 of ∞", which reads as a
 * limit that happens to be large rather than as no limit at all.
 */
export function usageLabel(invite) {
    return invite.max_uses === null
        ? trans('admin.invite_uses_unlimited', { count: invite.use_count })
        : trans('admin.invite_uses_of', { count: invite.use_count, max: invite.max_uses });
}

/**
 * UTC, sliced from the ISO string rather than via toLocaleString — this
 * renders during SSR too, and a timezone-dependent format produces different
 * output on the server than in the browser (a hydration mismatch).
 */
export function formatDate(iso) {
    return iso ? iso.slice(0, 10) : null;
}
