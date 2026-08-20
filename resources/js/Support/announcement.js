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

/**
 * Only absolute http(s) URLs and site-relative paths survive. Everything
 * else — javascript:, data:, and protocol-relative //host — returns null.
 *
 * This is the whole reason the banner can carry links at all: an <a> in
 * there can only ever receive an href that passed through here.
 */
function safeHref(url) {
    if (url.startsWith('//')) return null;
    if (url.startsWith('/')) return url;

    return /^https?:\/\//i.test(url) ? url : null;
}

// Link first, so a bolded label inside a link's text doesn't split the
// link in half. `[^)\s]+` keeps the URL from swallowing the rest of the
// sentence when someone forgets the closing paren.
const TOKEN = /\[([^\]]+)\]\(([^)\s]+)\)|\*\*([^*]+)\*\*/g;

/**
 * Parses the inline markdown subset the announcement supports — links and
 * bold — into a flat token list.
 *
 * Deliberately NOT a rich text editor, and deliberately not v-html. The
 * banner is one line of copy that occasionally needs to point somewhere.
 * Returning tokens that Vue renders with v-for makes injection impossible
 * by construction: text is interpolated, hrefs are validated above, and no
 * string ever reaches the DOM as markup.
 */
export function parseAnnouncement(text) {
    const source = text ?? '';
    const tokens = [];
    let last = 0;
    let match;

    TOKEN.lastIndex = 0;
    while ((match = TOKEN.exec(source)) !== null) {
        if (match.index > last) {
            tokens.push({ type: 'text', value: source.slice(last, match.index) });
        }

        if (match[1] !== undefined) {
            const href = safeHref(match[2]);
            // A rejected URL degrades to its own label rather than
            // disappearing, so a typo'd link still reads as a sentence
            // instead of silently dropping words out of the middle of it.
            tokens.push(href ? { type: 'link', value: match[1], href } : { type: 'text', value: match[1] });
        } else {
            tokens.push({ type: 'bold', value: match[3] });
        }

        last = match.index + match[0].length;
    }

    if (last < source.length) {
        tokens.push({ type: 'text', value: source.slice(last) });
    }

    return tokens;
}
