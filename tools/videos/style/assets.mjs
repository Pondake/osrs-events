// Renders the shared video style pieces (wordmark, tags, titles) with the site's own fonts.
import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const b64 = (f) => readFileSync(REPO + f).toString('base64');
const logo = 'data:image/svg+xml;base64,' + b64('/public/favicon.svg');
const rs = 'data:font/otf;base64,' + b64('/public/fonts/RuneScape-Bold-12.otf');
const head = `<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap">
<style>
@font-face { font-family: 'RS'; src: url(${rs}); }
html,body{margin:0;background:transparent}
.wrap{display:inline-flex;padding:24px}
.brand{display:flex;align-items:center;gap:14px;color:#f5f0e8;font:32px 'RS';letter-spacing:.5px;text-shadow:0 2px 0 #000}
.brand img{width:44px;height:44px;image-rendering:pixelated}
.tag{display:inline-flex;align-items:center;gap:10px;padding:9px 16px 9px 13px;border-radius:999px;background:rgba(14,12,10,.80);
  border:1px solid rgba(255,255,255,.14);color:#f5f0e8;font:600 19px 'Instrument Sans';box-shadow:0 6px 18px rgba(0,0,0,.35);backdrop-filter:blur(6px)}
.tag i{width:9px;height:9px;border-radius:50%;background:#fbbf24;box-shadow:0 0 0 4px rgba(251,191,36,.18)}
.label{font:600 13px 'Instrument Sans';letter-spacing:.14em;text-transform:uppercase;color:#a8a29e}
.title{font:700 30px Cinzel;color:#fbbf24;letter-spacing:.3px}
.sub{font:500 19px 'Instrument Sans';color:#a8a29e;margin-top:4px}
.right{text-align:right}
.vtitle{font:700 58px/1.05 Cinzel;color:#fbbf24;text-align:center}
.vsub{font:500 27px 'Instrument Sans';color:#d6d3d1;text-align:center;margin-top:14px}
.url{font:500 22px 'Instrument Sans';color:#a8a29e;margin-top:6px;text-align:center}
.col{display:flex;flex-direction:column;align-items:center}
</style>`;
const pieces = {
  brand: `<div class="brand"><img src="${logo}">OSRS Events</div>`,
  'tag-game': `<div class="tag"><i></i>In game, with the plugin</div>`,
  'tag-game-short': `<div class="tag"><i></i>In game</div>`,
  'tag-site': `<div class="tag"><i></i>On the site</div>`,
  'chat-label': `<div class="label">Game chat</div>`,
  title16: `<div class="right"><div class="title">Your kill, claimed on the card</div><div class="sub">The RuneLite plugin for clan bingo</div></div>`,
  vhead: `<div class="col"><div class="vtitle">Your kill,<br>claimed on the card</div><div class="vsub">The RuneLite plugin for clan bingo</div></div>`,
  vfoot: `<div class="col"><div class="brand"><img src="${logo}">OSRS Events</div><div class="url">Free for your clan · osrs-events.com</div></div>`,
};
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1600, height: 600 }, deviceScaleFactor: 1 });
for (const [name, html] of Object.entries(pieces)) {
  await p.setContent(`<html><head>${head}</head><body><div class="wrap">${html}</div></body></html>`);
  await p.waitForLoadState('networkidle'); await p.evaluate(() => document.fonts.ready);
  await p.locator('.wrap > *').first().screenshot({ path: path.join(HERE, name + '.png'), omitBackground: true });
}
await b.close();
console.log('ok');
