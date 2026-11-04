// Records the live S&L board while a driver rolls for 12 demo players.
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const require = createRequire(REPO + '/package.json');
const { chromium } = require('@playwright/test');
const ORIGIN = 'http://127.0.0.1:9600';
const art = (...a) => execFileSync('node', [path.join(HERE, 'art.mjs'), ...a], { cwd: REPO, encoding: 'utf8' });
const link = (who) => art('dev:login-link', who, `--base=${ORIGIN}`).trim().split('\n').pop().trim();

const [, eventId, lastTileId] = art('tinker', path.join(HERE, 'reset.php')).match(/RESET (\S+) (\S+)/);

// [ms after start, player, steps, toggle-last-tile?]
const SCRIPT = [
    [1500, 'romeo', 2], [1500, 'gnome', 3], [1500, 'bob', 4],
    [4500, 'hans', 6], [4500, 'gertrude', 5], [4500, 'juliet', 4],
    [7500, 'aggie', 2], [7500, 'thessalia', 6], [7500, 'wise', 4],
    [10500, 'bob', 5], [10500, 'ned', 6], [10500, 'duke', 4],
    [13500, 'ned', 6, true], [13500, 'aereck', 5], [13500, 'aggie', 3],
    [16500, 'bob', 2], [16500, 'gnome', 1], [16500, 'juliet', 6],
];
const DURATION = 24000;
// Slow the page's clock K times and speed the capture back up: the capture
// only manages ~10 fps, so this is what makes the result smooth.
const K = Number(process.env.K ?? 5);
const ZOOM = Number(process.env.ZOOM ?? 1.5);

const browser = await chromium.launch();

const players = {};
for (const key of new Set(SCRIPT.map((s) => s[1]))) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto(link('demo_' + key), { waitUntil: 'commit' });
    players[key] = page;
}

async function act(page, url, data = {}) {
    const token = decodeURIComponent((await page.context().cookies()).find((c) => c.name === 'XSRF-TOKEN').value);
    const res = await page.request.post(url, { headers: { 'X-XSRF-TOKEN': token, Referer: `${ORIGIN}/events/${eventId}` }, form: data, maxRedirects: 0 });
    if (res.status() >= 400) throw new Error(`${url} ${res.status()}`);
}

const viewerCtx = await browser.newContext({ viewport: { width: Math.round(1100 * ZOOM), height: Math.round(1000 * ZOOM) }, colorScheme: 'dark' });
const viewer = await viewerCtx.newPage();
await viewer.goto(link('e2e_member'));
await viewer.goto(`${ORIGIN}/events/${eventId}`);
await viewer.evaluate((z) => { document.documentElement.style.zoom = String(z); }, ZOOM);
const frame = viewer.locator('.grid-cols-7').locator('xpath=../..');
await frame.scrollIntoViewIfNeeded();
await viewer.evaluate(() => window.scrollBy(0, document.querySelector('.grid-cols-7').getBoundingClientRect().top - 110));
await viewer.waitForLoadState('networkidle').catch(() => {});
for (let i = 0; i < 3; i++) {
    const y = (await frame.boundingBox()).y;
    await viewer.evaluate((dy) => window.scrollBy(0, dy), (y - 170) / ZOOM);
}
await viewer.waitForTimeout(3000);
const broken = await viewer.evaluate(() => [...document.querySelectorAll('.grid-cols-7 img')].filter((i) => i.naturalWidth === 0).map((i) => i.src));
if (broken.length) console.log('BROKEN', broken);
await viewer.evaluate((k) => {
    const now = performance.now.bind(performance);
    const base = now();
    performance.now = () => base + (now() - base) / k;
    const raf = window.requestAnimationFrame.bind(window);
    window.requestAnimationFrame = (cb) => raf(() => cb(performance.now()));
    const st = window.setTimeout.bind(window);
    window.setTimeout = (f, ms = 0, ...a) => st(f, ms * k, ...a);
    const si = window.setInterval.bind(window);
    window.setInterval = (f, ms = 0, ...a) => si(f, ms * k, ...a);
    for (const a of document.getAnimations()) a.playbackRate = 1 / k;
}, K);
const box = await frame.boundingBox();

const out = path.join(HERE, 'frames');
rmSync(out, { recursive: true, force: true });
mkdirSync(out);
const cdp = await viewerCtx.newCDPSession(viewer);
await cdp.send('Animation.enable');
await cdp.send('Animation.setPlaybackRate', { playbackRate: 1 / K });
const meta = [];
cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
    const file = `f${String(meta.length).padStart(5, '0')}.jpg`;
    writeFileSync(path.join(out, file), Buffer.from(data, 'base64'));
    meta.push({ file, t: metadata.timestamp });
    await cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {});
});
await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: 4000, maxHeight: 4000 });
const t0 = Date.now() / 1000;

await Promise.all(SCRIPT.map(async ([at, key, steps, finish]) => {
    await new Promise((r) => setTimeout(r, at * K));
    await act(players[key], `${ORIGIN}/events/${eventId}/roll`, { force: String(steps) });
    if (finish) {
        await new Promise((r) => setTimeout(r, 300 * K));
        await act(players[key], `${ORIGIN}/events/${eventId}/tiles/${lastTileId}/toggle`);
    }
}));
await new Promise((r) => setTimeout(r, Math.max(0, DURATION * K - (Date.now() / 1000 - t0) * 1000)));
await cdp.send('Page.stopScreencast');
writeFileSync(path.join(HERE, 'frames.json'), JSON.stringify({ t0, box, meta, K, ZOOM }, null, 1));
await viewer.screenshot({ path: path.join(HERE, 'end.png') });
await browser.close();
console.log(`frames=${meta.length} box=${JSON.stringify(box)}`);
