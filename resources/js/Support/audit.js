import { trans } from 'laravel-vue-i18n';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

/**
 * Presentation per audit action — same shape and reasoning as
 * Support/announcement.js: the map lives outside the component so the list
 * and the filter dropdown label an action identically.
 *
 * Keys match AuditLog::ACTIONS. auditStyleFor() falls back to a neutral
 * entry rather than rendering a blank row if an action is logged before it's
 * added here.
 */
export const AUDIT_STYLES = {
    'user.role_granted': { color: 'success', icon: 'i-lucide-shield-plus' },
    'user.role_revoked': { color: 'warning', icon: 'i-lucide-shield-minus' },
    'user.permission_granted': { color: 'success', icon: 'i-lucide-key' },
    'user.permission_revoked': { color: 'warning', icon: 'i-lucide-key-round' },
    'user.deleted': { color: 'error', icon: 'i-lucide-user-x' },
    'team.created': { color: 'success', icon: 'i-lucide-users-round' },
    'team.updated': { color: 'neutral', icon: 'i-lucide-pencil' },
    'team.deleted': { color: 'error', icon: 'i-lucide-users-round' },
    'team.member_added': { color: 'success', icon: 'i-lucide-user-plus' },
    'team.member_removed': { color: 'warning', icon: 'i-lucide-user-minus' },
    'board.team_added': { color: 'success', icon: 'i-lucide-layout-grid' },
    'board.team_removed': { color: 'warning', icon: 'i-lucide-layout-grid' },
    'page.updated': { color: 'neutral', icon: 'i-lucide-file-pen' },
    'invite.created': { color: 'success', icon: 'i-lucide-ticket' },
    'invite.revoked': { color: 'error', icon: 'i-lucide-ticket-x' },
    'task.deleted': { color: 'error', icon: 'i-lucide-trash-2' },
    'settings.updated': { color: 'neutral', icon: 'i-lucide-sliders-horizontal' },
};

const FALLBACK = { color: 'neutral', icon: 'i-lucide-circle-dot' };

export function auditStyleFor(action) {
    return AUDIT_STYLES[action] ?? FALLBACK;
}

/** i18n keys can't hold dots as separators here, so 'user.deleted' → 'user_deleted'. */
export function auditLabel(action) {
    return trans(`audit.action_${action.replace('.', '_')}`);
}

export function auditActionOptions(actions) {
    return actions.map((action) => ({
        value: action,
        label: auditLabel(action),
        icon: auditStyleFor(action).icon,
    }));
}

// --- metadata values -------------------------------------------------------
//
// Values are returned as typed descriptors rather than pre-rendered strings
// so the component can draw a boolean as a coloured icon instead of the word
// "true" — which is a database detail, not something to show an admin.

const bool = (value) => ({ kind: 'bool', value });
const text = (value) => ({ kind: 'text', value });
const empty = () => ({ kind: 'empty' });

function roleLabel(role) {
    const key = `admin.role_label_${String(role).toLowerCase()}`;
    const label = trans(key);

    // laravel-vue-i18n echoes the key back when it's missing. A role added
    // server-side without a matching translation should still read as itself
    // rather than as "admin.role_label_whatever".
    return label === key ? role : label;
}

function permissionLabel(permission) {
    const key = `admin.permission_label_${String(permission).toLowerCase()}`;
    const label = trans(key);

    return label === key ? permission : label;
}

/** Long copy in a chip pushes everything else off the row. */
function truncate(value, max = 60) {
    const string = String(value);

    return string.length > max ? `${string.slice(0, max)}…` : string;
}

/**
 * How each metadata field is labelled and formatted.
 *
 * Registry rather than a switch in the component: adding a logged field means
 * adding one entry plus one translation key, and an unregistered field still
 * renders (as its raw key and value) instead of breaking the row.
 */
const FIELDS = {
    registration_open: { label: 'audit.field_registration_open', format: bool },
    default_board_size: {
        label: 'audit.field_default_board_size',
        format: (value) => (BOARD_SIZE_LABEL[value]
            ? text(trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[value], tiles: BOARD_TILE_COUNT[value] }))
            : text(value)),
    },
    default_dice_roll_limit: {
        label: 'audit.field_default_dice_roll_limit',
        // null is the app-wide convention for "unlimited" (see the boards
        // table), so it's a real value here, not a missing one.
        format: (value) => (value === null || value === undefined
            ? text(trans('admin.dice_roll_unlimited'))
            : text(trans('audit.value_rolls_per_day', { count: value }))),
    },
    kofi_url: { label: 'audit.field_kofi_url', format: (value) => (value ? text(value) : empty()) },
    announcement: {
        label: 'audit.field_announcement',
        format: (value) => (value ? text(truncate(value)) : empty()),
    },
    announcement_type: {
        label: 'audit.field_announcement_type',
        format: (value) => text(trans(`admin.site_announcement_type_${value}`)),
    },
    published: { label: 'audit.field_published', format: bool },
    blocks: { label: 'audit.field_blocks', format: (value) => text(String(value)) },
    board: { label: 'audit.field_board', format: (value) => (value ? text(value) : empty()) },
    short_code: { label: 'audit.field_short_code', format: (value) => text(value) },
    use_count: { label: 'audit.field_use_count', format: (value) => text(String(value)) },
    max_uses: {
        label: 'audit.field_max_uses',
        // null means unlimited here, same convention as the invite itself.
        format: (value) => (value === null || value === undefined
            ? text(trans('admin.invite_unlimited'))
            : text(String(value))),
    },
    name: { label: 'audit.field_name', format: (value) => (value ? text(value) : empty()) },
    icon_url: {
        label: 'audit.field_icon_url',
        // The URL itself is noise in a log row; whether there IS one is the
        // part an admin is reading for.
        format: (value) => (value ? text(trans('audit.value_set')) : empty()),
    },
    guild_id: { label: 'audit.field_guild_id', format: (value) => (value ? text(value) : empty()) },
    guild_name: { label: 'audit.field_guild_name', format: (value) => (value ? text(value) : empty()) },
    members: {
        label: 'audit.field_members',
        format: (value) => text(trans('audit.value_member_count', { count: value })),
    },
    role: { label: 'audit.field_role', format: (value) => text(roleLabel(value)) },
    permission: { label: 'audit.field_permission', format: (value) => text(permissionLabel(value)) },
    roles: {
        label: 'audit.field_roles',
        format: (value) => (value?.length ? text(value.map(roleLabel).join(', ')) : empty()),
    },
};

function describe(key, value) {
    const field = FIELDS[key];

    if (!field) return text(value === null || value === undefined ? '—' : String(value));

    return field.format(value);
}

/**
 * Flattens an action's metadata into labelled, formatted entries.
 *
 * Two shapes occur: a {from, to} pair (settings diffs) and a bare value
 * (which role, which permission, which roles a deleted user held).
 */
export function metadataEntries(metadata) {
    if (!metadata) return [];

    return Object.entries(metadata).map(([key, value]) => {
        const field = FIELDS[key];
        const label = field ? trans(field.label) : key;
        const isDiff = value && typeof value === 'object' && !Array.isArray(value) && 'to' in value;

        return isDiff
            ? { key, label, from: describe(key, value.from), to: describe(key, value.to) }
            : { key, label, to: describe(key, value) };
    });
}

/**
 * UTC, sliced straight out of the ISO string rather than run through
 * toLocaleString: this renders during SSR too, and a locale- or
 * timezone-dependent format produces different output on the server than in
 * the browser, which Vue reports as a hydration mismatch.
 */
export function formatTimestamp(iso) {
    return iso ? iso.slice(0, 16).replace('T', ' ') : '';
}
