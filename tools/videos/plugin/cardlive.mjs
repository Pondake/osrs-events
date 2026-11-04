// Records the plugin demo's card side live: three claims arrive on the stream, the last one wins.
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const ORIGIN = 'http://127.0.0.1:9600';
const art = (...a) => execFileSync('node', [path.join(HERE, 'art.mjs'), ...a], { cwd: REPO, encoding: 'utf8' });
const seed = (n) => { writeFileSync(path.join(HERE, 'state.txt'), String(n)); return art('tinker', path.join(HERE, 'seed.php')).match(/EVENT (\S+)/)[1]; };
writeFileSync(path.join(HERE, 'prefs.txt'), 'null'); 
const id = seed(0);
art('tinker', path.join(HERE, 'prefs.php'));
const b = await chromium.launch();
const ctx = await b.newContext({ viewport: { width: 900, height: 1215 }, deviceScaleFactor: 2, colorScheme: 'dark' });
const p = await ctx.newPage();
await p.goto(art('dev:login-link', 'demo_iron', `--base=${ORIGIN}`).trim().split('\n').pop().trim());
await p.goto(`${ORIGIN}/events/${id}`); await p.waitForLoadState('networkidle').catch(() => {});
await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(2000);
const K = Number(process.env.K ?? 4);
await p.evaluate((k) => {
    const now = performance.now.bind(performance); const base = now();
    performance.now = () => base + (now() - base) / k;
    const raf = window.requestAnimationFrame.bind(window);
    window.requestAnimationFrame = (cb) => raf(() => cb(performance.now()));
    const st = window.setTimeout.bind(window); window.setTimeout = (f, ms = 0, ...a) => st(f, ms * k, ...a);
    const si = window.setInterval.bind(window); window.setInterval = (f, ms = 0, ...a) => si(f, ms * k, ...a);
}, K);
const out = path.join(HERE, 'cardframes'); rmSync(out, { recursive: true, force: true }); mkdirSync(out);
const cdp = await ctx.newCDPSession(p); const meta = [];
await cdp.send('Animation.enable'); await cdp.send('Animation.setPlaybackRate', { playbackRate: 1 / K });
cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
  const file = `f${String(meta.length).padStart(5, '0')}.jpg`; writeFileSync(path.join(out, file), Buffer.from(data, 'base64'));
  meta.push({ file, t: metadata.timestamp }); await cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {});
});
const t0 = Date.now() / 1000;
await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 90, maxWidth: 4000, maxHeight: 4000 });
const clock = () => Date.now() / 1000 - t0;
const marks = [];
const count = () => p.evaluate(() => Number((document.body.innerText.match(/(\d) of 9 squares/) || [])[1] ?? -1));
for (let n = 1; n <= 3; n++) {
  await new Promise((r) => setTimeout(r, 1500));
  seed(n); const sent = clock();
  await p.waitForFunction((n) => (document.body.innerText.match(/(\d) of 9 squares/) || [])[1] === String(n), n, { timeout: 30000 });
  marks.push({ n, sent, shown: clock() }); console.log('state', n, 'shown', clock().toFixed(2));
}
await p.waitForSelector('canvas[data-celebration]', { timeout: 15000 }).then(() => marks.push({ confetti: clock() }));
await new Promise((r) => setTimeout(r, 4500 * K));
await cdp.send('Page.stopScreencast');
writeFileSync(path.join(HERE, 'cardframes.json'), JSON.stringify({ t0, meta, marks, K }, null, 1));
console.log(JSON.stringify(marks), meta.length);
await b.close();
