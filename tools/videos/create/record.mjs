// A host creates an event from a template and hands out an invite link.
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
art('tinker', path.join(HERE, 'reset.php'));
const K = Number(process.env.K ?? 5);
const ZOOM = 1.5;
const WHO = process.env.WHO ?? 'demo_host';

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: Number(process.env.VW ?? 1024), height: Number(process.env.VH ?? 900) }, deviceScaleFactor: ZOOM, colorScheme: 'dark' });
await ctx.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: ORIGIN });
const page = await ctx.newPage();
page.on('console', (m) => m.type() === 'error' && console.log('ERR', m.text()));
await page.goto(link(WHO));
await page.goto(`${ORIGIN}/events`);
await page.waitForLoadState('networkidle').catch(() => {});
await page.evaluate(() => {
    const c = document.createElement('div');
    c.id = 'demo-cursor';
    c.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 2l14 10-6.5 1.2L15 21l-3 1.3-3.4-7.7L4 19z" fill="#fff" stroke="#000" stroke-width="1.4" stroke-linejoin="round"/></svg>';
    Object.assign(c.style, { position: 'fixed', left: '-50px', top: '-50px', zIndex: 2147483647, pointerEvents: 'none', transition: 'transform 120ms' });
    document.documentElement.appendChild(c);
    addEventListener('mousemove', (e) => { const z = parseFloat(document.documentElement.style.zoom) || 1; c.style.left = e.clientX / z + 'px'; c.style.top = e.clientY / z + 'px'; }, true);
    addEventListener('mousedown', () => { c.style.transform = 'scale(0.8)'; }, true);
    addEventListener('mouseup', () => { c.style.transform = ''; }, true);
});
await page.waitForTimeout(2000);
let mouse = { x: 800, y: 560 };
await page.mouse.move(mouse.x, mouse.y);
await page.evaluate((k) => {
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

const out = path.join(HERE, 'frames');
rmSync(out, { recursive: true, force: true });
mkdirSync(out);
const cdp = await ctx.newCDPSession(page);
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
const clock = () => (Date.now() / 1000 - t0) * 1000 / K;
const wait = (ms) => new Promise((r) => setTimeout(r, ms * K));
const marks = {};
const mark = (name) => { marks[name] = clock(); console.log(name, clock().toFixed(0)); };

async function glide(locator, ms = 420) {
    const b = await locator.boundingBox();
    const to = { x: b.x + Math.min(b.width / 2, 95), y: b.y + b.height / 2 };
    const n = 16;
    for (let i = 1; i <= n; i++) {
        const e = i / n, f = e < 0.5 ? 2 * e * e : 1 - (-2 * e + 2) ** 2 / 2;
        await page.mouse.move(mouse.x + (to.x - mouse.x) * f, mouse.y + (to.y - mouse.y) * f);
        await new Promise((r) => setTimeout(r, (ms / n) * K));
    }
    mouse = to;
}
async function click(locator) {
    await locator.waitFor();
    await locator.scrollIntoViewIfNeeded();
    await glide(locator);
    await page.mouse.down();
    await wait(80);
    await page.mouse.up();
}
const type = (loc, text) => loc.pressSequentially(text, { delay: 55 * K });

await wait(700);
mark('start');
await click(page.getByRole('button', { name: 'Create event' }));
const dlg = page.getByRole('dialog');
await dlg.waitFor();
await wait(900);
const search = dlg.getByPlaceholder('Search templates');
await click(search);
await type(search, 'week');
await wait(700);
await click(dlg.getByText('Skill of the Week', { exact: true }));
mark('template');
await wait(900);
const title = dlg.getByPlaceholder('e.g. Dragon Slayer Journey');
await click(title);
await page.keyboard.press('End');
await type(title, ': Mining');
await wait(500);
await click(dlg.getByRole('button', { name: /^Next/ }));
mark('format');
await wait(700);
await click(dlg.getByText('Overall', { exact: true }).last());
await wait(400);
await page.screenshot({ path: path.join(HERE, 'sel.png') });
await page.keyboard.type('mining', { delay: 55 * K });
await wait(400);
await click(page.getByRole('option', { name: /^Mining/ }).first());
await wait(600);
await click(dlg.getByRole('button', { name: /^Next/ }));
mark('access');
await wait(600);
await click(dlg.getByText('Invite only', { exact: false }).first());
await wait(600);
await click(dlg.getByRole('button', { name: 'Create event' }));
await page.waitForURL(/\/events\/[0-9a-f-]{36}/);
mark('created');
await wait(1600);
await click(page.getByRole('button', { name: 'Manage invites' }));
const inv = page.getByRole('dialog');
await wait(700);
await click(inv.getByRole('button', { name: 'Create invite' }));
await inv.getByRole('button', { name: 'Link' }).waitFor();
await wait(900);
await click(inv.getByRole('button', { name: 'Link' }));
mark('copied');
await wait(2600);
await cdp.send('Page.stopScreencast');
console.log('clipboard', await page.evaluate(() => navigator.clipboard.readText()).catch((e) => String(e)));
writeFileSync(path.join(HERE, 'frames.json'), JSON.stringify({ t0, meta, K, ZOOM, marks }, null, 1));
await page.screenshot({ path: path.join(HERE, 'end.png') });
await browser.close();
console.log('frames', meta.length);
