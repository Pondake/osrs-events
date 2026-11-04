// Player claims a bingo square, host approves it; both pages recorded at once.
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
const [, eventId] = art('tinker', path.join(HERE, 'reset.php')).match(/RESET (\S+) (\S+)/);
const K = Number(process.env.K ?? 5);
const ZOOM = 1.5;
const PROOF = 'https://oldschool.runescape.wiki/images/Vorkath%27s_head_detail.png';

const browser = await chromium.launch();
const sides = {};
for (const [side, who] of [['player', 'demo_bob'], ['host', 'e2e_owner']]) {
    const ctx = await browser.newContext({ viewport: { width: 1200, height: 1560 }, colorScheme: 'dark' });
    const page = await ctx.newPage();
    await page.goto(link(who));
    await page.goto(`${ORIGIN}/events/${eventId}`);
    await page.evaluate((z) => { document.documentElement.style.zoom = String(z); }, ZOOM);
    await page.waitForLoadState('networkidle').catch(() => {});
    const card = page.locator('.grid-cols-5').first();
    for (let i = 0; i < 3; i++) {
        const y = (await card.boundingBox()).y;
        await page.evaluate((dy) => window.scrollBy(0, dy), (y - 170) / ZOOM);
    }
    await page.evaluate(() => {
        const c = document.createElement('div');
        c.id = 'demo-cursor';
        c.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 2l14 10-6.5 1.2L15 21l-3 1.3-3.4-7.7L4 19z" fill="#fff" stroke="#000" stroke-width="1.4" stroke-linejoin="round"/></svg>';
        Object.assign(c.style, { position: 'fixed', left: '-50px', top: '-50px', zIndex: 2147483647, pointerEvents: 'none', transition: 'transform 120ms' });
        document.body.appendChild(c);
        addEventListener('mousemove', (e) => { const z = parseFloat(document.documentElement.style.zoom) || 1; c.style.left = e.clientX / z + 'px'; c.style.top = e.clientY / z + 'px'; }, true);
        addEventListener('mousedown', () => { c.style.transform = 'scale(0.8)'; }, true);
        addEventListener('mouseup', () => { c.style.transform = ''; }, true);
    });
    await page.waitForTimeout(2500);
    sides[side] = { page, ctx, card, mouse: { x: 1080, y: 1420 } };
}

for (const s of Object.values(sides)) {
    await s.page.mouse.move(s.mouse.x, s.mouse.y);
    await s.page.evaluate((k) => {
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
}

const meta = {};
const t0 = Date.now() / 1000;
for (const [side, s] of Object.entries(sides)) {
    const out = path.join(HERE, 'frames-' + side);
    rmSync(out, { recursive: true, force: true });
    mkdirSync(out);
    const cdp = await s.ctx.newCDPSession(s.page);
    await cdp.send('Animation.enable');
    await cdp.send('Animation.setPlaybackRate', { playbackRate: 1 / K });
    meta[side] = [];
    cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
        const file = `f${String(meta[side].length).padStart(5, '0')}.jpg`;
        writeFileSync(path.join(out, file), Buffer.from(data, 'base64'));
        meta[side].push({ file, t: metadata.timestamp });
        await cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {});
    });
    await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: 4000, maxHeight: 4000 });
    s.cdp = cdp;
}

const clock = () => (Date.now() / 1000 - t0) * 1000 / K;
const until = (ms) => new Promise((r) => setTimeout(r, Math.max(0, (ms - clock()) * K)));
async function glide(s, locator, ms = 450) {
    const b = await locator.boundingBox();
    const to = { x: b.x + b.width / 2, y: b.y + b.height / 2 };
    const n = 18;
    for (let i = 1; i <= n; i++) {
        const e = i / n, f = e < 0.5 ? 2 * e * e : 1 - (-2 * e + 2) ** 2 / 2;
        await s.page.mouse.move(s.mouse.x + (to.x - s.mouse.x) * f, s.mouse.y + (to.y - s.mouse.y) * f);
        await new Promise((r) => setTimeout(r, (ms / n) * K));
    }
    s.mouse = to;
}
async function click(s, locator) {
    await glide(s, locator);
    await s.page.mouse.down();
    await new Promise((r) => setTimeout(r, 90 * K));
    await s.page.mouse.up();
}
const P = sides.player, H = sides.host;
P.page.waitForSelector('canvas[data-celebration]', { timeout: 120000 }).then(() => console.log('confetti', clock().toFixed(0))).catch(() => console.log('no confetti'));

await until(700);
await click(P, P.page.locator('.grid-cols-5').getByText('Kill Vorkath once'));
await until(1900);
const proof = P.page.getByPlaceholder('https://');
await click(P, proof);
await proof.pressSequentially(PROOF, { delay: 9 * K });
await until(3700);
await click(P, P.page.getByRole('button', { name: 'Submit claim' }));
console.log('submitted', clock().toFixed(0));
await P.page.getByRole('dialog').waitFor({ state: 'detached' });
await P.page.evaluate(() => document.activeElement?.blur());
await glide(P, { boundingBox: async () => ({ x: 1100, y: 1430, width: 0, height: 0 }) }, 500);
await H.page.getByRole('button', { name: /waiting for review/ }).waitFor({ timeout: 60000 });
console.log('host sees', clock().toFixed(0));
await until(Math.max(5000, clock() + 500));
await click(H, H.page.getByRole('button', { name: /waiting for review/ }));
await H.page.getByRole('button', { name: 'Approve' }).waitFor();
await until(Math.max(7000, clock() + 1400));
await click(H, H.page.getByRole('button', { name: 'Approve' }));
console.log('approved', clock().toFixed(0));
await until(clock() + 1300);
await click(H, H.page.getByRole('dialog').getByRole('button', { name: 'Close', exact: true }).last());
await glide(H, { boundingBox: async () => ({ x: 1100, y: 1430, width: 0, height: 0 }) }, 500);
await until(clock() + 5200);
for (const s of Object.values(sides)) await s.cdp.send('Page.stopScreencast');
writeFileSync(path.join(HERE, 'frames.json'), JSON.stringify({ t0, meta, K, ZOOM }, null, 1));
for (const [side, s] of Object.entries(sides)) await s.page.screenshot({ path: path.join(HERE, `end-${side}.png`) });
await browser.close();
console.log('frames', meta.player.length, meta.host.length);
