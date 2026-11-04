// Title card, step bands and end card for the homepage intro.
import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const icon = 'data:image/png;base64,' + readFileSync(REPO + '/public/android-chrome-192x192.png').toString('base64');
const head = `<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Cinzel+Decorative:wght@700&family=Inter:wght@400;600&display=swap">
<style>body{margin:0;font-family:Inter,sans-serif;color:#f5f0e8;background:transparent}
.full{width:1920px;height:1080px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:26px;
 background:radial-gradient(90% 90% at 50% 40%,#3a2a10 0%,#14110e 65%,#0c0b0a 100%)}
h1{font-family:Cinzel;color:#fbbf24;margin:0;font-size:96px;line-height:1.05}
.sub{font-size:40px;opacity:.85;max-width:1300px}
.brand{display:flex;align-items:center;gap:22px;font-family:'Cinzel Decorative';font-size:60px}
.brand img{width:96px;height:96px}
.band{width:1920px;height:170px;display:flex;align-items:center;gap:30px;padding:0 70px;box-sizing:border-box;
 background:linear-gradient(180deg,#1d1710 0%,#0c0b0a 100%)}
.n{flex:none;width:92px;height:92px;border-radius:50%;background:#f5a623;color:#1c1917;font:700 54px Cinzel;display:flex;align-items:center;justify-content:center}
.t{font-family:Cinzel;font-size:54px;color:#fbbf24;line-height:1.1}
.s{font-size:30px;opacity:.85;margin-top:6px}
.col{width:690px;height:1080px;display:flex;flex-direction:column;justify-content:center;gap:28px;padding:0 60px 0 70px;box-sizing:border-box;
 background:radial-gradient(120% 70% at 0% 50%,#2e2210 0%,#14110e 60%,#0c0b0a 100%)}
.col .n{width:120px;height:120px;font-size:70px}
.col .t{font-size:66px}
.col .s{font-size:34px;line-height:1.35;margin-top:0}
</style>`;
const pages = {
    title: `<div class="full"><div class="brand"><img src="${icon}">OSRS Events</div><h1>Your clan's next event,<br>start to finish</h1><div class="sub">Bingo, Snakes &amp; Ladders and races, tracked for you. Free.</div></div>`,
    end: `<div class="full"><div class="brand"><img src="${icon}">OSRS Events</div><h1>Free for your clan</h1><div class="sub">osrs-events.com</div></div>`,
    band1: `<div class="band"><div class="n">1</div><div><div class="t">Pick a template</div><div class="s">Skill of the Week, Boss of the Month, bingo, Snakes &amp; Ladders &mdash; or start from scratch.</div></div></div>`,
    band2: `<div class="band"><div class="n">2</div><div><div class="t">Share the invite link</div><div class="s">Post it in your clan Discord. Players join from the link.</div></div></div>`,
    band3: `<div class="band"><div class="n">3</div><div><div class="t">Players claim, you check</div><div class="s">A screenshot for the host &mdash; or the RuneLite plugin claims it for them.</div></div></div>`,
    band4: `<div class="band"><div class="n">4</div><div><div class="t">Everyone follows along live</div><div class="s">Boards and standings update by themselves, from Wise Old Man and the plugin.</div></div></div>`,
    col1: `<div class="col"><div class="n">1</div><div class="t">Pick a template</div><div class="s">Skill of the Week, Boss of the Month, bingo, Snakes &amp; Ladders &mdash; or start from scratch.</div></div>`,
    col2: `<div class="col"><div class="n">2</div><div class="t">Share the invite link</div><div class="s">Post it in your clan Discord. Players join from the link.</div></div>`,
    col3: `<div class="col"><div class="n">3</div><div class="t">Players claim, you check</div><div class="s">A screenshot for the host &mdash; or the RuneLite plugin claims it for them.</div></div>`,
    col4: `<div class="col"><div class="n">4</div><div class="t">Everyone follows along live</div><div class="s">Boards and standings update by themselves.</div></div>`,
};
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1920, height: 1080 } });
for (const [k, html] of Object.entries(pages)) {
    await p.setContent(`<html><head>${head}</head><body>${html}</body></html>`);
    await p.waitForLoadState('networkidle'); await p.evaluate(() => document.fonts.ready);
    const el = p.locator('body > div').first();
    await el.screenshot({ path: path.join(HERE, k + '.png') });
}
await b.close();
