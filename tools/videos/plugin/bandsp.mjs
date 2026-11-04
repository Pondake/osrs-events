// Renders the 1080x1920 frame (top/bottom brand bands, transparent middle) for each vertical clip.
import { createRequire } from 'node:module';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const clips = {
    sl: { top: 420, h: 'Snakes &amp; Ladders<br>for your clan', s: 'A task on every tile. A daily roll. The whole board live.' },
    plugin: { top: 230, h: 'Your kill,<br>on the card', s: 'The RuneLite plugin claims the square for you.' },
    bingo: { top: 260, h: 'Clan bingo,<br>checked by a host', s: 'Claim with a screenshot. The host approves. The card fills in.' },
};
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1080, height: 1920 } });
for (const [key, c] of Object.entries(clips).filter(([k]) => k === 'plugin')) {
    const bottom = key === 'plugin' ? 135 : 1920 - c.top - (key === 'sl' ? 1080 : 1404);
    await p.setContent(`<html><head><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Cinzel+Decorative:wght@700&family=Inter:wght@400;600&display=swap">
<style>body{margin:0;width:1080px;height:1920px;background:transparent;font-family:Inter,sans-serif;color:#f5f0e8}
.top{position:absolute;left:0;right:0;top:0;height:${c.top}px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;
 background:radial-gradient(120% 140% at 50% 0%,#3a2a10 0%,#14110e 60%)}
.bot{position:absolute;left:0;right:0;bottom:0;height:${bottom}px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:14px;
 background:radial-gradient(120% 140% at 50% 100%,#3a2a10 0%,#14110e 60%)}
h1{font-family:Cinzel;font-size:${key === 'plugin' ? 50 : 64}px;line-height:1.05;margin:0;color:#fbbf24;letter-spacing:.5px}
p{font-size:30px;margin:14px 60px 0;opacity:.85}
.brand{display:flex;align-items:center;gap:18px;font-family:'Cinzel Decorative';font-size:${key === 'plugin' ? 32 : 44}px}
.brand img{width:64px;height:64px}
.url{font-size:28px;opacity:.75}
</style></head><body>
<div class="top"><h1>${c.h}</h1><p>${c.s}</p></div>
<div class="bot"><div class="brand"><img src="http://127.0.0.1:9600/android-chrome-192x192.png">OSRS Events</div><div class="url">Free for your clan · osrs-events.com</div></div>
</body></html>`);
    await p.waitForLoadState('networkidle'); await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(HERE, `bands-${key}.png`), omitBackground: true });
}
await b.close();
