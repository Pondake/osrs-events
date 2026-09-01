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

// Written out per colour rather than built as `bg-${color}/10`: Tailwind
// scans source text for class names, so an interpolated one is never
// generated and the banner would render with no background at all.
//
// Here rather than in a component for the same reason the styles above are:
// three places draw this banner now — AppRoot's site-wide one, the admin
// form's preview, and the lock screen's (a chromeless page, so AppRoot's
// banner never reaches it). Two copies had already drifted once.
const BANNER_BG = {
    primary: 'bg-primary/10',
    success: 'bg-success/10',
    warning: 'bg-warning/10',
    error: 'bg-error/10',
};

const BANNER_ICON = {
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
};

export function bannerBgFor(type) {
    return BANNER_BG[styleFor(type).color];
}

export function bannerIconFor(type) {
    return BANNER_ICON[styleFor(type).color];
}

/** Options for the admin dropdown, labels translated at call time. */
export function announcementTypeOptions() {
    return Object.entries(ANNOUNCEMENT_STYLES).map(([value, style]) => ({
        value,
        label: trans(`admin.site_announcement_type_${value}`),
        icon: style.icon,
    }));
}
