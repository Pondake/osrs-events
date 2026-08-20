import { trans } from 'laravel-vue-i18n';

/**
 * Colour and icon per announcement type.
 *
 * Lives here rather than in either component because two places render the
 * same banner: the live one in AppRoot, and the preview in the admin form.
 * If those drift, the preview stops being a preview.
 *
 * Keys match Setting::ANNOUNCEMENT_TYPES — the server validates against that
 * list, so an unknown key can't be stored, but styleFor() still falls back
 * to info rather than rendering an unstyled banner if one ever appears.
 */
export const ANNOUNCEMENT_STYLES = {
    info: { color: 'primary', icon: 'i-lucide-megaphone' },
    success: { color: 'success', icon: 'i-lucide-circle-check' },
    warning: { color: 'warning', icon: 'i-lucide-triangle-alert' },
    error: { color: 'error', icon: 'i-lucide-circle-alert' },
};

export function styleFor(type) {
    return ANNOUNCEMENT_STYLES[type] ?? ANNOUNCEMENT_STYLES.info;
}

/** Options for the admin dropdown, labels translated at call time. */
export function announcementTypeOptions() {
    return Object.entries(ANNOUNCEMENT_STYLES).map(([value, style]) => ({
        value,
        label: trans(`admin.site_announcement_type_${value}`),
        icon: style.icon,
    }));
}
