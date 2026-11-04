// Bob's phone on a team bingo card: Hans scores, the card and standings update live, a push drops in.
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const ORIGIN = 'http://127.0.0.1:9600';
const art = (...a) => execFileSync('node', [path.join(HERE, 'art.mjs'), ...a], { cwd: REPO, encoding: 'utf8' });
const [, eventId] = art('tinker', path.join(HERE, 'seed.php')).match(/EVENT (\S+)/);
const link = art('dev:login-link', 'demo_bob', `--base=${ORIGIN}`).trim().split('\n').pop().trim();
const K = Number(process.env.K ?? 5);
const SCROLL = Number(process.env.SCROLL ?? 240);

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 360, height: 640 }, deviceScaleFactor: 3, colorScheme: 'dark', isMobile: true, hasTouch: true });
const page = await ctx.newPage();
await page.goto(link);
await page.goto(`${ORIGIN}/events/${eventId}`);
await page.waitForLoadState('networkidle').catch(() => {});
await page.evaluate((y) => window.scrollTo(0, y), SCROLL);
await page.evaluate(() => {
    const n = document.createElement('div');
    n.id = 'demo-push';
    n.innerHTML = `<div style="display:flex;gap:10px;align-items:flex-start">
      <img src="/android-chrome-192x192.png" style="width:38px;height:38px;border-radius:9px;flex:none">
      <div style="flex:1;min-width:0">
        <div style="display:flex;justify-content:space-between;font-size:11px;opacity:.7;margin-bottom:2px"><span>OSRS Events</span><span>now</span></div>
        <div style="font-weight:600;font-size:14px">Your team scored</div>
        <div style="font-size:13px;line-height:1.3;opacity:.9">Hans got Kill Vorkath once approved in Clan Team Bingo.</div>
      </div></div>`;
    Object.assign(n.style, { position: 'fixed', left: '8px', right: '8px', top: '8px', zIndex: 2147483646, padding: '11px 13px',
        borderRadius: '18px', background: 'rgba(245,242,238,.94)', color: '#1c1917', fontFamily: 'system-ui, Segoe UI, sans-serif',
        boxShadow: '0 10px 30px rgba(0,0,0,.45)', transform: 'translateY(-130%)', transition: 'transform 420ms cubic-bezier(.2,.9,.3,1.15)' });
    document.body.appendChild(n);
    const t = document.createElement('div');
    t.id = 'demo-tap';
    Object.assign(t.style, { position: 'fixed', width: '34px', height: '34px', marginLeft: '-17px', marginTop: '-17px', borderRadius: '50%',
        background: 'rgba(255,255,255,.55)', border: '2px solid rgba(0,0,0,.25)', zIndex: 2147483647, pointerEvents: 'none', opacity: 0,
        transform: 'scale(.6)', transition: 'opacity 160ms, transform 160ms' });
    document.body.appendChild(t);
});
await page.waitForTimeout(2500);
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
const t0 = Date.now() / 1000;
await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: 4000, maxHeight: 4000 });
const clock = () => (Date.now() / 1000 - t0) * 1000 / K;
const until = (ms) => new Promise((r) => setTimeout(r, Math.max(0, (ms - clock()) * K)));
const marks = {};

await until(1000);
art('tinker', path.join(HERE, 'score.php'));
const vork = page.locator('.grid-cols-5 > *').nth(7);
await page.waitForFunction(() => /IW/.test(document.querySelectorAll('.grid-cols-5 > *')[7]?.textContent ?? ''), null, { timeout: 120000 });
marks.live = clock();
console.log('live', marks.live.toFixed(0));
await until(marks.live + 150);
await page.evaluate(() => { document.getElementById('demo-push').style.transform = 'translateY(0)'; });
await until(marks.live + 3600);
const tap = (x, y, on) => page.evaluate(([x, y, on]) => {
    const t = document.getElementById('demo-tap');
    Object.assign(t.style, { left: x + 'px', top: y + 'px', opacity: on ? 1 : 0, transform: on ? 'scale(1)' : 'scale(.6)' });
}, [x, y, on]);
marks.out = clock();
await page.evaluate(() => { const n = document.getElementById('demo-push'); n.style.transition = 'transform 300ms ease-in'; n.style.transform = 'translateY(-130%)'; });
await until(clock() + 2200);
await cdp.send('Page.stopScreencast');
writeFileSync(path.join(HERE, 'frames.json'), JSON.stringify({ t0, meta, K, marks }, null, 1));
await page.screenshot({ path: path.join(HERE, 'end.png') });
await browser.close();
console.log('frames', meta.length);
