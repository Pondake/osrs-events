import { describe, expect, it } from 'vitest';

import { isExternal, parseInline, safeHref } from '@/Support/richtext';

/**
 * The inline-markdown subset used by the announcement banner and the CMS
 * page blocks.
 *
 * safeHref is a security boundary, not a formatter: the text it vets comes
 * from the database, an admin typed it, and the result goes straight into an
 * href. So most of this file is about what it must REFUSE.
 */
describe('safeHref', () => {
    it('keeps an ordinary absolute URL', () => {
        expect(safeHref('https://oldschool.runescape.wiki/w/Zulrah')).toBe('https://oldschool.runescape.wiki/w/Zulrah');
        expect(safeHref('http://example.com')).toBe('http://example.com');
    });

    it('keeps a site-relative path', () => {
        expect(safeHref('/events')).toBe('/events');
    });

    it('keeps a bare mailto address', () => {
        expect(safeHref('mailto:hello@example.com')).toBe('mailto:hello@example.com');
    });

    it('trims surrounding whitespace', () => {
        expect(safeHref('  https://example.com  ')).toBe('https://example.com');
    });

    // ------------------------------------------------------------- refusals

    it('refuses javascript:', () => {
        expect(safeHref('javascript:alert(1)')).toBeNull();
        expect(safeHref('JavaScript:alert(1)')).toBeNull();
        expect(safeHref('  javascript:alert(1)')).toBeNull();
    });

    it('refuses data:', () => {
        expect(safeHref('data:text/html,<script>alert(1)</script>')).toBeNull();
    });

    it('refuses vbscript: and other schemes', () => {
        expect(safeHref('vbscript:msgbox(1)')).toBeNull();
        expect(safeHref('file:///etc/passwd')).toBeNull();
        expect(safeHref('ftp://example.com')).toBeNull();
    });

    /** `//evil.com` inherits the current scheme and leaves the site. */
    it('refuses a protocol-relative URL', () => {
        expect(safeHref('//evil.example')).toBeNull();
    });

    /**
     * The one that is easy to miss. Browsers following the WHATWG URL spec
     * treat a backslash as a forward slash in a special scheme, so
     * `/\evil.example` resolves as `//evil.example` — off-site, from what
     * looks like a site-relative path. Stored content must not be able to
     * hand a reader that.
     */
    it('refuses a backslash disguised as a relative path', () => {
        expect(safeHref('/\\evil.example')).toBeNull();
        expect(safeHref('/\\/evil.example')).toBeNull();
        expect(safeHref('\\\\evil.example')).toBeNull();
    });

    /**
     * mailto: carries no query on purpose — `?body=…` lets stored content
     * prefill a message the reader did not write.
     */
    it('refuses a mailto with a query', () => {
        expect(safeHref('mailto:x@y.com?body=send%20me%20your%20password')).toBeNull();
        expect(safeHref('mailto:x@y.com&cc=z@y.com')).toBeNull();
    });

    it('refuses anything that is not a string', () => {
        expect(safeHref(null)).toBeNull();
        expect(safeHref(undefined)).toBeNull();
        expect(safeHref(42)).toBeNull();
        expect(safeHref({})).toBeNull();
    });

    it('refuses an empty string', () => {
        expect(safeHref('')).toBeNull();
        expect(safeHref('   ')).toBeNull();
    });
});

describe('isExternal', () => {
    it('keeps site-relative links in the tab', () => {
        expect(isExternal('/events')).toBe(false);
    });

    /** target=_blank on a mailto leaves an empty tab behind. */
    it('does not treat mailto as external', () => {
        expect(isExternal('mailto:hello@example.com')).toBe(false);
    });

    it('opens off-site links in a new tab', () => {
        expect(isExternal('https://example.com')).toBe(true);
    });
});

describe('parseInline', () => {
    it('returns plain text as one token', () => {
        expect(parseInline('Just words')).toEqual([{ type: 'text', value: 'Just words' }]);
    });

    it('parses bold', () => {
        expect(parseInline('read the **rules**')).toEqual([
            { type: 'text', value: 'read the ' },
            { type: 'bold', value: 'rules' },
        ]);
    });

    it('parses a link', () => {
        expect(parseInline('see [the wiki](https://example.com) now')).toEqual([
            { type: 'text', value: 'see ' },
            { type: 'link', value: 'the wiki', href: 'https://example.com' },
            { type: 'text', value: ' now' },
        ]);
    });

    /**
     * A rejected URL degrades to its own label rather than disappearing, so
     * a typo'd link still reads as a sentence instead of silently dropping
     * words out of the middle of it.
     */
    it('degrades an unsafe link to plain text without losing the words', () => {
        // The URL pattern stops at the first `)`, so the trailing one falls
        // through as text — the point is that no token is a link and the
        // label survives, not the exact split.
        const tokens = parseInline('click [here](javascript:alert(1)) please');

        expect(tokens.some((t) => t.type === 'link')).toBe(false);
        expect(tokens.map((t) => t.value).join('')).toContain('here');
        expect(tokens.map((t) => t.value).join('')).toContain('please');
    });

    it('handles several tokens in one line', () => {
        const tokens = parseInline('**Summer** bingo — [sign up](/events) or read the **rules**');

        expect(tokens.map((t) => t.type)).toEqual(['bold', 'text', 'link', 'text', 'bold']);
    });

    /**
     * The regex is a module-level /g, so lastIndex survives between calls
     * unless it is reset. Two identical calls returning different answers is
     * the classic symptom.
     */
    it('gives the same answer when called twice', () => {
        const text = 'see [the wiki](https://example.com)';

        expect(parseInline(text)).toEqual(parseInline(text));
    });

    it('treats a non-string as empty', () => {
        expect(parseInline(null)).toEqual([]);
        expect(parseInline(undefined)).toEqual([]);
    });

    /** A URL with no closing paren must not swallow the rest of the line. */
    it('does not run a malformed link into the following text', () => {
        const tokens = parseInline('[broken](https://example.com and more words');

        expect(tokens).toEqual([{ type: 'text', value: '[broken](https://example.com and more words' }]);
    });

    /**
     * Markup in the source stays a TEXT token — it is not stripped, because
     * it does not need to be: callers render tokens with v-for and `{{ }}`,
     * so text is interpolated and escaped. What must never happen is markup
     * being promoted into a link or an href, which is where it would reach
     * the DOM as markup.
     */
    it('leaves html as inert text rather than promoting it', () => {
        const tokens = parseInline('<script>alert(1)</script> and **bold**');

        for (const token of tokens) {
            if (token.value.includes('<script>')) {
                expect(token.type).toBe('text');
                expect(token.href).toBeUndefined();
            }
        }
    });

    /** An href can only ever be something safeHref approved. */
    it('never emits an href safeHref would refuse', () => {
        const tokens = parseInline('[a](javascript:alert(1)) [b](//evil.example) [c](/ok) [d](https://ok.example)');

        for (const token of tokens.filter((t) => t.type === 'link')) {
            expect(safeHref(token.href)).toBe(token.href);
        }
    });
});
