/**
 * Reads the contrast of every piece of visible text on the page, the way the
 * walkthrough does by hand: foreground against the background it is really
 * drawn on, measured, not judged by eye.
 *
 * Runs inside the page (page.evaluate), so it must not close over anything.
 * The background is the stack of translucent backgrounds from the root down,
 * flattened on a canvas — which also turns oklch(), color-mix() and every
 * other syntax the stylesheet uses into plain sRGB. A background that is a
 * gradient or an image cannot be flattened to one colour: text over one is
 * measured against the flat stand-in and flagged `unknown` when it fails, since
 * the real pixel behind it may differ.
 *
 * WCAG 1.4.3: 4.5:1, or 3:1 for large text (24px, or 18.66px and bold).
 *
 * @returns {{text: string, ratio: number, required: number, by: string, unknown?: boolean}[]}
 */
export function measureContrast() {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    const paint = (color) => {
        ctx.fillStyle = color;
        ctx.fillRect(0, 0, 1, 1);
    };
    // Its own canvas: the first one holds the backgrounds flattened so far.
    const probe = document.createElement('canvas');
    probe.width = probe.height = 1;
    const probeCtx = probe.getContext('2d', { willReadFrequently: true });
    const alphaOf = (color) => {
        probeCtx.clearRect(0, 0, 1, 1);
        probeCtx.fillStyle = color;
        probeCtx.fillRect(0, 0, 1, 1);

        return probeCtx.getImageData(0, 0, 1, 1).data[3];
    };
    const pixel = () => [...ctx.getImageData(0, 0, 1, 1).data.slice(0, 3)];
    const luminance = ([r, g, b]) =>
        [r, g, b]
            .map((v) => {
                v /= 255;

                return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
            })
            .reduce((sum, v, i) => sum + [0.2126, 0.7152, 0.0722][i] * v, 0);
    const ratioOf = (a, b) => {
        const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);

        return (hi + 0.05) / (lo + 0.05);
    };

    const chain = (element) => {
        const list = [];

        for (let node = element; node; node = node.parentElement) list.unshift(node);

        return list;
    };

    const seen = new Map();
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);

    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const text = node.textContent.trim();
        const element = node.parentElement;

        if (!text || !element || ['SCRIPT', 'STYLE', 'NOSCRIPT', 'TITLE'].includes(element.tagName)) continue;

        const box = element.getBoundingClientRect();

        if (box.width === 0 || box.height === 0) continue;

        const style = getComputedStyle(element);

        if (style.visibility === 'hidden' || style.display === 'none') continue;
        // A disabled control is exempt from the criterion, and so is text that is not there for a reader.
        if (element.closest('[disabled], [aria-disabled="true"], [aria-hidden="true"], [hidden], .sr-only')) continue;

        const layers = chain(element);
        let opacity = 1;
        let unknown = false;

        paint('#ffffff');

        for (const layer of layers) {
            const layerStyle = getComputedStyle(layer);

            opacity *= Number(layerStyle.opacity);

            const opaque = alphaOf(layerStyle.backgroundColor) === 255;

            // What is under an opaque background cannot show through it.
            if (opaque) unknown = false;
            if (layerStyle.backgroundImage !== 'none' || (layerStyle.backdropFilter && layerStyle.backdropFilter !== 'none')) unknown = true;
            if (alphaOf(layerStyle.backgroundColor) > 0) paint(layerStyle.backgroundColor);
        }

        const background = pixel();

        paint(style.color);

        const flat = pixel();
        const alpha = opacity;
        const foreground = flat.map((v, i) => Math.round(v * alpha + background[i] * (1 - alpha)));
        const ratio = ratioOf(foreground, background);

        const size = parseFloat(style.fontSize);
        const large = size >= 24 || (size >= 18.66 && Number(style.fontWeight) >= 700);
        const required = large ? 3 : 4.5;

        // Over a gradient the flattened colour is a stand-in, so it only counts when it fails.
        if (ratio >= required) continue;

        const by = `${element.tagName.toLowerCase()}.${String(element.className).split(/\s+/).filter(Boolean).slice(0, 3).join('.')}`;
        const key = `${by}|${foreground}|${background}`;

        if (seen.has(key)) continue;

        seen.set(key, {
            text: text.slice(0, 40),
            ratio: Math.floor(ratio * 100) / 100,
            required,
            by,
            ...(unknown ? { unknown: true } : {}),
        });
    }

    return [...seen.values()];
}

/**
 * The colour roles of the design system, one reading each: these are what a
 * theme change breaks first, and a failure names the token rather than an
 * element. `null` when the page has no element using the role.
 */
export function measureRoles() {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const bg = getComputedStyle(document.body).backgroundColor;
    const rgb = (color) => {
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, 1, 1);
        ctx.fillStyle = color;
        ctx.fillRect(0, 0, 1, 1);

        return [...ctx.getImageData(0, 0, 1, 1).data.slice(0, 3)];
    };
    const luminance = (channels) =>
        channels
            .map((v) => {
                v /= 255;

                return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
            })
            .reduce((sum, v, i) => sum + [0.2126, 0.7152, 0.0722][i] * v, 0);
    const ratio = (a, b) => {
        const [hi, lo] = [luminance(rgb(a)), luminance(rgb(b))].sort((x, y) => y - x);

        return Number(((hi + 0.05) / (lo + 0.05)).toFixed(2));
    };

    const roles = {};

    for (const role of ['text-highlighted', 'text-default', 'text-toned', 'text-muted', 'text-dimmed', 'text-primary', 'text-success', 'text-warning', 'text-error', 'text-info']) {
        const element = [...document.querySelectorAll(`.${role}`)].find((el) => el.getBoundingClientRect().width > 0 && el.textContent.trim());

        if (element) roles[role] = ratio(getComputedStyle(element).color, bg);
    }

    return roles;
}
