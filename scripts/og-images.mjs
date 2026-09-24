/**
 * Renders the link-preview images (og:image) from an HTML template.
 *
 *     node scripts/og-images.mjs
 *     node scripts/og-images.mjs --preview <dir>   also writes each image at 400px wide
 *
 * Writes public/og-image.png (every page without its own) and
 * public/images/og/<name>.png (pages that pass `image` to useSeoData).
 *
 * After changing any output, bump OG_IMAGE_VERSION in
 * resources/js/Composables/useSeo.js. Discord, X and Reddit cache a preview by
 * its URL for days; the version in the query string is what makes them fetch
 * the new one.
 *
 * Needs network access for Cinzel and Instrument Sans (Google Fonts, same
 * source app.blade.php uses) and Playwright's Chromium
 * (`pnpm exec playwright install chromium`). The run fails instead of writing
 * an image when a font did not load, when any text is below 4.5:1 against
 * the pixels behind it, or when text runs into the artwork or the bottom strip.
 */
import { chromium } from '@playwright/test';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIDTH = 1200;
const HEIGHT = 630;
const MIN_CONTRAST = 4.5;

// Board geometry, taken from the image this replaced so the board keeps its
// place: 5x5 tiles of 60px on a 68px step, top-left at (792, 150).
const ART_X = 792;
const ART_Y = 150;
const STEP = 68;
const TILE = 60;

const C = {
    bg: '#1c1919',
    bgAlt: '#211c1c',
    gold: '#d9b45e',
    parchment: '#e6d9b8',
    tile: '#272121',
    tileAlt: '#332b2b',
    tileEdge: '#181414',
    red: '#ef4444',
    redFill: '#582f2f',
    green: '#22c55e',
    greenFill: '#183823',
    goldFill: '#55462c',
    ember: '#e0762f',
};

const TYPES = ['Snakes & Ladders', 'Bingo', 'Skill races', 'Drop races'];

// The strip along the bottom: the two promises first, then what the app plugs into.
const PROMISES = ['Free', 'No ads'];
const FEATURES = ['Discord', 'RuneLite plugin', 'Wise Old Man'];

const VARIANTS = [
    {
        out: 'public/og-image.png',
        subtitle: 'Free events for your clan',
        chips: TYPES,
        art: snakesBoard,
    },
    {
        out: 'public/images/og/beta.png',
        badge: 'Closed beta',
        subtitle: 'Testers welcome',
        chips: TYPES,
        art: snakesBoard,
    },
    {
        out: 'public/images/og/bingo.png',
        subtitle: 'Bingo for OSRS clans',
        detail: 'A card of goals, claimed with a screenshot. Line or full house wins.',
        features: ['Discord', 'RuneLite plugin'],
        art: bingoCard,
    },
    {
        out: 'public/images/og/skill-race.png',
        subtitle: 'Skill races for OSRS clans',
        detail: 'Pick a skill, race on XP gained. Tracked from the hiscores.',
        features: ['Discord', 'Wise Old Man'],
        art: (assets) => raceBoard(assets.skillIcon),
    },
    {
        out: 'public/images/og/drop-race.png',
        subtitle: 'Drop races for OSRS clans',
        detail: 'Pick a boss, race on kills. Tracked from the hiscores.',
        features: ['Discord', 'Wise Old Man'],
        art: (assets) => raceBoard(assets.bossIcon),
    },
];

function tileRect(row, col, fill, edge, edgeWidth = 1) {
    const x = ART_X + col * STEP;
    const y = ART_Y + row * STEP;
    const inset = edgeWidth / 2;
    return `<rect x="${x + inset}" y="${y + inset}" width="${TILE - edgeWidth}" height="${TILE - edgeWidth}" rx="6" fill="${fill}" stroke="${edge}" stroke-width="${edgeWidth}"/>`;
}

function checker(row, col) {
    return (row + col) % 2 ? C.tileAlt : C.tile;
}

function tiles(special) {
    let out = '';
    for (let row = 0; row < 5; row++) {
        for (let col = 0; col < 5; col++) {
            const s = special(row, col);
            out += s ? tileRect(row, col, s.fill, s.edge, 2) : tileRect(row, col, checker(row, col), C.tileEdge);
        }
    }
    return out;
}

function snakesBoard() {
    const special = (row, col) => {
        if (row === 0 && col === 3) return { fill: C.redFill, edge: C.red };
        if (row === 0 && col === 4) return { fill: C.goldFill, edge: C.gold };
        if (row === 4 && col === 1) return { fill: C.greenFill, edge: C.green };
        return null;
    };
    return `<svg class="art" viewBox="0 0 ${WIDTH} ${HEIGHT}" width="${WIDTH}" height="${HEIGHT}">
        ${tiles(special)}
        <path d="M888 420 V292" stroke="#21ab53" stroke-width="3" fill="none" stroke-linecap="round"/>
        <path d="M878 298 L888 286 L898 298" stroke="#21ab53" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M1024 210 C1024 262 962 276 958 340" stroke="#cf3d3d" stroke-width="3" fill="none" stroke-linecap="round"/>
        <path d="M950 330 L958 343 L967 331" stroke="#cf3d3d" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;
}

function bingoCard() {
    const claimed = new Set(['0,1', '1,3', '3,0', '3,4', '4,2']);
    const lineRow = 2;
    const special = (row, col) => {
        if (row === lineRow) return { fill: C.goldFill, edge: C.gold };
        if (claimed.has(`${row},${col}`)) return { fill: C.greenFill, edge: C.green };
        return null;
    };
    const check = (row, col, color) => {
        const x = ART_X + col * STEP + TILE / 2;
        const y = ART_Y + row * STEP + TILE / 2;
        return `<path d="M${x - 12} ${y} L${x - 3} ${y + 9} L${x + 13} ${y - 9}" stroke="${color}" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`;
    };
    let marks = '';
    for (let col = 0; col < 5; col++) marks += check(lineRow, col, C.gold);
    for (const key of claimed) {
        const [row, col] = key.split(',').map(Number);
        marks += check(row, col, C.green);
    }
    return `<svg class="art" viewBox="0 0 ${WIDTH} ${HEIGHT}" width="${WIDTH}" height="${HEIGHT}">${tiles(special)}${marks}</svg>`;
}

/**
 * A standings list: one icon for what is being raced on, four ranked bars.
 * No names — a preview has no room to make them legible, and a made-up name
 * can turn out to be somebody's character.
 */
function raceBoard(icon) {
    const fills = [
        [1, C.gold],
        [0.78, C.ember],
        [0.56, C.green],
        [0.38, C.red],
    ];
    const barX = STEP;
    const barWidth = 5 * STEP - 8 - barX;
    const rows = fills
        .map(
            ([share, color], i) => `
        <div class="race-row" style="top:${(i + 1) * STEP}px">
            <div class="race-rank" data-text>${i + 1}</div>
            <div class="race-track" style="left:${barX}px;width:${barWidth}px">
                <div style="width:${Math.round(share * 100)}%;background:${color}"></div>
            </div>
        </div>`,
        )
        .join('');
    return `<div class="race" style="left:${ART_X}px;top:${ART_Y}px">
        <img class="race-icon" src="${icon.src}" width="${icon.width * 3}" height="${icon.height * 3}" alt="">
        ${rows}
    </div>`;
}

async function dataUri(path, type) {
    return `data:${type};base64,${(await readFile(join(root, path))).toString('base64')}`;
}

async function pngIcon(path) {
    const buffer = await readFile(join(root, path));
    return {
        src: `data:image/png;base64,${buffer.toString('base64')}`,
        width: buffer.readUInt32BE(16),
        height: buffer.readUInt32BE(20),
    };
}

function page(variant, assets) {
    const escape = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    const chips = variant.chips
        ? `<ul class="chips">${variant.chips.map((c) => `<li><span data-text>${escape(c)}</span></li>`).join('')}</ul>`
        : '';
    const detail = variant.detail ? `<p class="detail" data-text>${escape(variant.detail)}</p>` : '';
    const badge = variant.badge ? `<span class="badge"><span data-text>${escape(variant.badge)}</span></span>` : '';
    const footer = [
        ...PROMISES.map((t) => `<span class="promise" data-text>${escape(t)}</span>`),
        ...(variant.features ?? FEATURES).map((t) => `<span data-text>${escape(t)}</span>`),
    ].join('<span class="dot">•</span>');

    return `<!doctype html><html><head><meta charset="utf-8">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Instrument+Sans:wght@500;600&display=block">
<style>
@font-face { font-family: 'RuneScape Bold 12'; src: url('${assets.runescapeFont}') format('opentype'); font-weight: 700; }
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { width: ${WIDTH}px; height: ${HEIGHT}px; overflow: hidden; }
body {
    position: relative;
    background:
        radial-gradient(ellipse 90% 90% at 30% 45%, transparent 40%, rgba(0,0,0,.35) 100%),
        conic-gradient(${C.bgAlt} 25%, ${C.bg} 0 50%, ${C.bgAlt} 0 75%, ${C.bg} 0) 0 0 / ${STEP * 2}px ${STEP * 2}px;
    font-family: 'Instrument Sans', sans-serif;
    color: ${C.parchment};
}
.bar { position: absolute; left: 0; right: 0; bottom: 0; height: 10px; background: ${C.gold}; }
.art { position: absolute; left: 0; top: 0; }
.left {
    position: absolute; left: 88px; top: 0; bottom: 110px; width: 690px;
    display: flex; flex-direction: column; justify-content: center;
}
.brand { display: flex; align-items: center; gap: 20px; }
.logo { width: 80px; height: 80px; flex: none; shape-rendering: crispEdges; }
.subtitle-row { display: flex; align-items: center; gap: 20px; margin-top: 22px; }
.badge {
    font-weight: 600; font-size: 32px; line-height: 1;
    padding: 12px 22px; border-radius: 999px;
    background: ${C.gold}; color: ${C.bg};
}
h1 {
    font-family: 'Cinzel', serif; font-weight: 700; font-size: 84px; line-height: 1; white-space: nowrap;
    color: ${C.gold}; letter-spacing: -0.5px;
}
.subtitle { font-weight: 600; font-size: 42px; line-height: 1.15; }
.features {
    position: absolute; left: 88px; right: 88px; bottom: 44px;
    display: flex; align-items: center; gap: 18px;
    font-weight: 600; font-size: 32px; line-height: 1; color: ${C.parchment};
}
.features .promise { color: ${C.gold}; }
.features .dot { color: ${C.gold}; opacity: .6; }
.detail { font-weight: 500; font-size: 32px; line-height: 1.3; margin-top: 16px; max-width: 640px; }
.chips { list-style: none; display: grid; grid-template-columns: max-content max-content; gap: 12px; margin-top: 26px; }
.chips li {
    font-weight: 500; font-size: 30px; line-height: 1;
    padding: 12px 18px; border-radius: 10px;
    background: rgba(0,0,0,.25); border: 2px solid rgba(217,180,94,.45);
}
.race { position: absolute; width: ${5 * STEP - 8}px; height: ${5 * STEP - 8}px; }
.race-icon { position: absolute; left: ${(TILE - 1) / 2}px; top: ${TILE / 2}px; transform: translate(-50%, -50%); image-rendering: pixelated; }
.race-row { position: absolute; left: 0; right: 0; height: ${TILE}px; }
.race-rank {
    position: absolute; left: 0; top: 0; width: ${TILE}px; height: ${TILE}px;
    display: flex; align-items: center; justify-content: center;
    background: ${C.tile}; border: 1px solid ${C.tileEdge}; border-radius: 6px;
    font-family: 'RuneScape Bold 12'; font-size: 36px; color: ${C.parchment};
    -webkit-font-smoothing: none;
}
.race-track { position: absolute; top: 12px; height: 36px; border-radius: 6px; background: ${C.tile}; border: 1px solid ${C.tileEdge}; overflow: hidden; }
.race-track > div { height: 100%; opacity: .85; }
body.measure [data-text] { visibility: hidden; }
</style></head><body>
<main class="left">
    <div class="brand">${assets.logo}<h1 data-text>OSRS Events</h1></div>
    <div class="subtitle-row"><p class="subtitle" data-text>${escape(variant.subtitle)}</p>${badge}</div>
    ${detail}${chips}
</main>
<footer class="features">${footer}</footer>
${variant.art(assets)}
<div class="bar"></div>
</body></html>`;
}

async function assertFonts(tab) {
    const missing = await tab.evaluate(async () => {
        const wanted = ['700 84px Cinzel', '600 42px "Instrument Sans"', '500 30px "Instrument Sans"', '700 36px "RuneScape Bold 12"'];
        const out = [];
        for (const font of wanted) {
            const faces = await document.fonts.load(font);
            if (!faces.length) out.push(font);
        }
        await document.fonts.ready;
        return out;
    });
    if (missing.length) throw new Error(`fonts did not load: ${missing.join(', ')}`);
}

/**
 * Worst-case WCAG contrast for every [data-text] node: its colour against
 * the lightest and darkest pixel behind it, read from a second render with
 * the text hidden. Measured, so a gradient or a border that ends up under a
 * glyph counts.
 */
async function measure(tab, backgroundPng) {
    return tab.evaluate(async (bgSrc) => {
        const img = new Image();
        img.src = bgSrc;
        await img.decode();
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);

        const lum = ([r, g, b]) => {
            const f = (v) => ((v /= 255) <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4);
            return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
        };
        const ratio = (a, b) => (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);

        return [...document.querySelectorAll('[data-text]')].map((el) => {
            const range = document.createRange();
            range.selectNodeContents(el);
            const rects = [...range.getClientRects()];
            const fg = lum(getComputedStyle(el).color.match(/\d+/g).slice(0, 3).map(Number));
            let lo = 1;
            let hi = 0;
            for (const r of rects) {
                const x = Math.max(0, Math.floor(r.left));
                const y = Math.max(0, Math.floor(r.top));
                const w = Math.min(canvas.width - x, Math.ceil(r.width));
                const h = Math.min(canvas.height - y, Math.ceil(r.height));
                const px = ctx.getImageData(x, y, w, h).data;
                for (let i = 0; i < px.length; i += 4) {
                    const l = lum([px[i], px[i + 1], px[i + 2]]);
                    lo = Math.min(lo, l);
                    hi = Math.max(hi, l);
                }
            }
            const box = el.getBoundingClientRect();
            return {
                text: el.textContent.trim(),
                inArt: !!el.closest('.race'),
                inFooter: !!el.closest('.features'),
                right: Math.max(...rects.map((r) => r.right)),
                fontSize: parseFloat(getComputedStyle(el).fontSize),
                contrast: Math.min(ratio(fg, lo), ratio(fg, hi)),
                bottom: box.bottom,
            };
        });
    }, backgroundPng);
}

const previewIndex = process.argv.indexOf('--preview');
const previewDir = previewIndex > -1 ? process.argv[previewIndex + 1] : null;

const logoSvg = (await readFile(join(root, 'resources/images/logo/osrs-events-logo-color.svg'), 'utf8'))
    .replace(/<svg[^>]*>/, '<svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">');
const assets = {
    logo: logoSvg,
    runescapeFont: await dataUri('public/fonts/RuneScape-Bold-12.otf', 'font/otf'),
    skillIcon: await pngIcon('public/images/osrs/skills/woodcutting.png'),
    bossIcon: await pngIcon('public/images/osrs/bosses/vorkath.png'),
};

const browser = await chromium.launch();
const tab = await browser.newPage({ viewport: { width: WIDTH, height: HEIGHT }, deviceScaleFactor: 1 });
let failed = false;

try {
    for (const variant of VARIANTS) {
        await tab.setContent(page(variant, assets), { waitUntil: 'networkidle' });
        await assertFonts(tab);

        const image = await tab.screenshot({ type: 'png' });
        await tab.evaluate(() => document.body.classList.add('measure'));
        const background = await tab.screenshot({ type: 'png' });
        const footerTop = await tab.evaluate(() => document.querySelector('.features').getBoundingClientRect().top);
        const results = await measure(tab, `data:image/png;base64,${background.toString('base64')}`);

        const problems = [];
        for (const r of results) {
            if (r.contrast < MIN_CONTRAST) problems.push(`"${r.text}" contrast ${r.contrast.toFixed(2)}:1`);
            if (r.fontSize < 28) problems.push(`"${r.text}" is ${r.fontSize}px, under 28px`);
            if (r.inFooter && r.right > WIDTH - 88) problems.push(`"${r.text}" runs off the right edge`);
            if (!r.inArt && !r.inFooter && r.right > ART_X - 24) problems.push(`"${r.text}" runs into the artwork (right edge ${Math.round(r.right)}px)`);
            if (!r.inFooter && r.bottom > footerTop - 20) problems.push(`"${r.text}" runs into the bottom strip`);
            if (r.bottom > HEIGHT - 30) problems.push(`"${r.text}" runs off the bottom`);
        }

        const worst = Math.min(...results.map((r) => r.contrast));
        console.log(`${variant.out}  worst contrast ${worst.toFixed(2)}:1  smallest text ${Math.min(...results.map((r) => r.fontSize))}px`);
        for (const r of results) console.log(`    ${r.contrast.toFixed(2)}:1  ${r.fontSize}px  ${r.text}`);

        if (problems.length) {
            failed = true;
            console.error(`  not written:\n    ${problems.join('\n    ')}`);
            continue;
        }

        const out = join(root, variant.out);
        await mkdir(dirname(out), { recursive: true });
        await writeFile(out, image);

        if (previewDir) {
            const small = await browser.newPage({ viewport: { width: 400, height: 210 } });
            await small.setContent(
                `<body style="margin:0"><img style="display:block;width:400px;height:210px" src="data:image/png;base64,${image.toString('base64')}"></body>`,
            );
            await mkdir(previewDir, { recursive: true });
            await small.screenshot({ path: join(previewDir, variant.out.split('/').pop().replace('.png', '-400.png')) });
            await small.close();
        }
    }
} finally {
    await browser.close();
}

if (failed) process.exit(1);
