// Records the skill race standings while waves of "syncs" reorder them.
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const ORIGIN = 'http://127.0.0.1:9600';
const art = (...a) => execFileSync('node', [path.join(HERE, 'art.mjs'), ...a], { cwd: REPO, encoding: 'utf8' });
const link = (who) => art('dev:login-link', who, `--base=${ORIGIN}`).trim().split('\n').pop().trim();
const wave = (n) => { writeFileSync(path.join(HERE, 'wave.txt'), String(n)); return art('tinker', path.join(HERE, 'wave.php')); };

const [, eventId] = wave(0).match(/WAVE 0 (\S+)/);
const WAVES = [[2500, 1], [5500, 2], [8500, 3], [11500, 4]];
const DURATION = 15500;
const K = Number(process.env.K ?? 5);
const ZOOM = 1.5;

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: Math.round(760 * ZOOM), height: Math.round(1000 * ZOOM) }, colorScheme: 'dark' });
const viewer = await ctx.newPage();
await viewer.goto(link('demo_bob'));
await viewer.goto(`${ORIGIN}/events/${eventId}`);
await viewer.evaluate((z) => { document.documentElement.style.zoom = String(z); }, ZOOM);
const card = viewer.locator('ul.divide-y').first().locator('xpath=ancestor::div[contains(@class,"rounded")][1]');
for (let i = 0; i < 3; i++) {
    const y = (await card.boundingBox()).y;
    await viewer.evaluate((dy) => window.scrollBy(0, dy), (y - 150) / ZOOM);
}
await viewer.waitForTimeout(3000);
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
}, K);
const box = await card.boundingBox();

const out = path.join(HERE, 'frames');
rmSync(out, { recursive: true, force: true });
mkdirSync(out);
const cdp = await ctx.newCDPSession(viewer);
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
for (const [at, n] of WAVES) {
    await new Promise((r) => setTimeout(r, Math.max(0, at * K - (Date.now() / 1000 - t0) * 1000)));
    console.log(wave(n).trim(), ((Date.now() / 1000 - t0) / K).toFixed(2));
}
await new Promise((r) => setTimeout(r, Math.max(0, DURATION * K - (Date.now() / 1000 - t0) * 1000)));
await cdp.send('Page.stopScreencast');
writeFileSync(path.join(HERE, 'frames.json'), JSON.stringify({ t0, box, meta, K, ZOOM }, null, 1));
await viewer.screenshot({ path: path.join(HERE, 'end.png') });
await browser.close();
console.log(`frames=${meta.length} box=${JSON.stringify(box)}`);
