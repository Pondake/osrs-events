import { vi } from 'vitest';

/**
 * `trans()` from laravel-vue-i18n, stubbed.
 *
 * Every helper under Support/ reaches for it, and the real one needs a
 * loaded language file plus the Vite plugin that compiles them. What these
 * tests actually care about is which KEY was chosen and which placeholders
 * were filled — not the English wording, which is a translator's business
 * and changes without the logic changing.
 *
 * So the stub returns the key with its placeholders substituted, PREFIXED
 * with `t:`. A test can then assert `t:skills.mining` rather than `Mining`,
 * and stays green when somebody rewords the copy.
 *
 * The prefix is not decoration. laravel-vue-i18n returns the key itself when
 * a translation is missing, and Support/metrics.js keys off exactly that to
 * fall back to the raw slug. A stub that returned the bare key would make
 * that fallback fire on every lookup, so every test would be exercising the
 * missing-translation path while appearing to test the normal one.
 */
// A vi.fn(), not a plain function, so a test that needs the OTHER behaviour
// — trans() handing the key back, which is what "missing translation" looks
// like — can say so for one call with mockImplementationOnce.
vi.mock('laravel-vue-i18n', () => ({
    trans: vi.fn((key, replacements = {}) => 't:'.concat(Object.entries(replacements).reduce(
        (out, [name, value]) => out.replace(`:${name}`, String(value)),
        key,
    ))),
    wTrans: vi.fn((key) => key),
    loadLanguageAsync: () => Promise.resolve(),
}));
