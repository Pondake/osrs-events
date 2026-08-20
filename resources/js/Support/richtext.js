/**
 * A deliberately tiny inline markdown subset — links and bold — parsed into
 * tokens rather than HTML.
 *
 * The rule this module exists to enforce: text that came from a database is
 * never handed to v-html. Callers render the token list with v-for, so text
 * is always interpolated and an <a> can only ever receive an href that
 * safeHref() approved. That holds whether the text is a one-line
 * announcement banner or a paragraph in a CMS-authored page.
 *
 * Originally written for the announcement banner (Support/announcement.js);
 * moved here when the page-block renderer needed the same guarantees.
 */

/**
 * Only absolute http(s) URLs, site-relative paths and bare mailto: addresses
 * survive. Everything else — javascript:, data:, and protocol-relative
 * //host — returns null.
 *
 * mailto: carries no query string on purpose. `mailto:x@y.com?body=…` is a
 * real shape, but it lets stored content prefill a message the reader didn't
 * write, and no page needs it.
 */
export function safeHref(url) {
    if (typeof url !== 'string') return null;

    const trimmed = url.trim();

    if (trimmed.startsWith('//')) return null;
    if (trimmed.startsWith('/')) return trimmed;
    if (/^mailto:[^\s?&]+@[^\s?&]+$/i.test(trimmed)) return trimmed;

    return /^https?:\/\//i.test(trimmed) ? trimmed : null;
}

/**
 * Site-relative links stay in the tab; off-site ones open a new one. mailto:
 * is neither — it hands off to a mail client, and target="_blank" on it
 * leaves an empty tab behind.
 */
export function isExternal(href) {
    return !href.startsWith('/') && !href.startsWith('mailto:');
}

// Link first, so a bolded label inside a link's text doesn't split the link
// in half. `[^)\s]+` keeps the URL from swallowing the rest of the sentence
// when someone forgets the closing paren.
const TOKEN = /\[([^\]]+)\]\(([^)\s]+)\)|\*\*([^*]+)\*\*/g;

/**
 * Parses the supported subset into a flat token list of
 * {type: 'text'|'bold'|'link', value, href?}.
 */
export function parseInline(text) {
    const source = typeof text === 'string' ? text : '';
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
