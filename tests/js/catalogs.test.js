import { describe, expect, it } from 'vitest';

import { ANNOUNCEMENT_STYLES, announcementTypeOptions, styleFor } from '@/Support/announcement';
import { AUDIT_STYLES, auditActionOptions, auditLabel, auditStyleFor } from '@/Support/audit';
import { ICON_GROUPS } from '@/Support/iconCatalog';
import { INVITE_STYLES, inviteStatusOptions, inviteStyleFor, usageLabel } from '@/Support/invite';
import { formatDate } from '@/Support/invite';

/**
 * The presentation catalogues.
 *
 * Each of these exists because two components render the same thing — the
 * live announcement banner and its admin preview, the audit list and its
 * filter, the invite row and its summary tile — and a map in one place is
 * what stops them labelling the same value differently. The tests are mostly
 * about the fallbacks: a value the map has not caught up with must render as
 * something rather than as nothing.
 */
describe('announcement styles', () => {
    it('styles every type the server will store', () => {
        // Mirrors Setting::ANNOUNCEMENT_TYPES.
        for (const type of ['info', 'success', 'warning', 'error']) {
            expect(ANNOUNCEMENT_STYLES[type], type).toBeTruthy();
            expect(styleFor(type).icon, type).toMatch(/^i-/);
        }
    });

    /** The server validates the list, but a blank banner is the one outcome
     *  worth ruling out entirely. */
    it('falls back to info rather than rendering unstyled', () => {
        expect(styleFor('catastrophe')).toEqual(ANNOUNCEMENT_STYLES.info);
        expect(styleFor(undefined)).toEqual(ANNOUNCEMENT_STYLES.info);
    });

    it('offers every type in the admin dropdown', () => {
        const options = announcementTypeOptions();

        expect(options).toHaveLength(Object.keys(ANNOUNCEMENT_STYLES).length);
        expect(options.every((o) => o.icon.startsWith('i-'))).toBe(true);
    });
});

describe('audit styles', () => {
    it('styles a known action', () => {
        expect(auditStyleFor('user.deleted').color).toBe('error');
    });

    /** An action logged before it is added here must not render a blank row. */
    it('falls back for an action it has not caught up with', () => {
        const style = auditStyleFor('something.new');

        expect(style.color).toBe('neutral');
        expect(style.icon).toMatch(/^i-/);
    });

    /**
     * i18n keys cannot hold the dot as a separator, so 'user.deleted' has to
     * become 'user_deleted' — a mismatch here shows as a raw key on the page.
     */
    it('turns the dot into an underscore for the translation key', () => {
        expect(auditLabel('user.role_granted')).toBe('t:audit.action_user_role_granted');
    });

    it('builds filter options from whatever the server sends', () => {
        const options = auditActionOptions(['user.deleted', 'team.created']);

        expect(options.map((o) => o.value)).toEqual(['user.deleted', 'team.created']);
        expect(options.every((o) => o.icon.startsWith('i-'))).toBe(true);
    });

    it('has an icon for every action it styles', () => {
        for (const [action, style] of Object.entries(AUDIT_STYLES)) {
            expect(style.icon, action).toMatch(/^i-/);
            expect(style.color, action).toBeTruthy();
        }
    });
});

describe('invite styles', () => {
    it('styles every status the server reports', () => {
        for (const status of ['active', 'unused', 'exhausted', 'expired']) {
            expect(INVITE_STYLES[status], status).toBeTruthy();
        }
    });

    it('falls back for an unknown status', () => {
        expect(inviteStyleFor('mystery').icon).toMatch(/^i-/);
    });

    it('builds filter options', () => {
        expect(inviteStatusOptions(['active', 'expired']).map((o) => o.value)).toEqual(['active', 'expired']);
    });

    /**
     * "3 used", not "3 of ∞" — the second reads as a limit that happens to
     * be large rather than as no limit at all.
     */
    it('says used rather than out-of-infinity when there is no cap', () => {
        expect(usageLabel({ use_count: 3, max_uses: null })).toBe('t:admin.invite_uses_unlimited');
        expect(usageLabel({ use_count: 3, max_uses: 10 })).toBe('t:admin.invite_uses_of');
    });

    /**
     * Sliced from the ISO string rather than run through toLocaleString:
     * this renders during SSR too, and a timezone-dependent format produces
     * different output on the server than in the browser — a hydration
     * mismatch.
     */
    it('formats a date without touching the local timezone', () => {
        expect(formatDate('2026-08-22T23:30:00.000000Z')).toBe('2026-08-22');
        expect(formatDate(null)).toBeNull();
    });
});

describe('icon catalogue', () => {
    /**
     * This list IS the contract with vite.config.js, which feeds it straight
     * into clientBundle.icons. An icon that is not bundled renders as a
     * permanently empty <svg> with no network request — so a malformed name
     * here is a picker offering something that silently does not draw.
     */
    it('names every icon in a bundleable form', () => {
        for (const group of ICON_GROUPS) {
            for (const icon of group.icons) {
                expect(icon, icon).toMatch(/^i-(lucide|simple-icons)-[a-z0-9-]+$/);
            }
        }
    });

    it('gives every group a translation key rather than English', () => {
        for (const group of ICON_GROUPS) {
            expect(group.key, group.key).toBeTruthy();
            expect(group.label, group.key).toMatch(/^icons\./);
        }
    });

    /** A duplicate would render the same icon twice in the picker. */
    it('lists each icon once across all groups', () => {
        const all = ICON_GROUPS.flatMap((group) => group.icons);

        expect(all).toHaveLength(new Set(all).size);
    });

    it('has no empty groups', () => {
        for (const group of ICON_GROUPS) {
            expect(group.icons.length, group.key).toBeGreaterThan(0);
        }
    });
});
