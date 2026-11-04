// Headings, step columns and title cards for every demo video, in the shared style.
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
.big .brand{font-size:52px;gap:20px}.big .brand img{width:72px;height:72px}
.tag{display:inline-flex;align-items:center;gap:10px;padding:9px 16px 9px 13px;border-radius:999px;background:rgba(14,12,10,.80);
  border:1px solid rgba(255,255,255,.14);color:#f5f0e8;font:600 19px 'Instrument Sans';box-shadow:0 6px 18px rgba(0,0,0,.35)}
.tag i{width:9px;height:9px;border-radius:50%;background:#fbbf24;box-shadow:0 0 0 4px rgba(251,191,36,.18)}
.title{font:700 30px Cinzel;color:#fbbf24;letter-spacing:.3px}
.sub{font:500 19px 'Instrument Sans';color:#a8a29e;margin-top:4px}
.right{text-align:right}.center{text-align:center}
.step{width:520px;display:flex;flex-direction:column;gap:22px}
.step .k{font:600 17px 'Instrument Sans';letter-spacing:.16em;text-transform:uppercase;color:#fbbf24;display:flex;align-items:center;gap:12px}
.step .k::before{content:'';width:34px;height:2px;background:#fbbf24;border-radius:2px}
.step .t{font:700 58px/1.08 Cinzel;color:#f5f0e8}
.step .s{font:500 29px/1.45 'Instrument Sans';color:#a8a29e}
.card{width:1500px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:30px}
.card h1{font:700 92px/1.06 Cinzel;color:#fbbf24;margin:0}
.card .s{font:500 36px 'Instrument Sans';color:#d6d3d1}
.card .u{font:500 30px 'Instrument Sans';color:#a8a29e}
</style>`;
const brand = (cls = '') => `<div class="brand ${cls}"><img src="${logo}">OSRS Events</div>`;
const heading = (t, s, cls = 'right') => `<div class="${cls}"><div class="title">${t}</div><div class="sub">${s}</div></div>`;
const step = (n, t, s) => `<div class="step"><div class="k">Step ${n}</div><div class="t">${t}</div><div class="s">${s}</div></div>`;
const card = (h, s, u = '') => `<div class="card big">${brand()}<h1>${h}</h1><div class="s">${s}</div>${u ? `<div class="u">${u}</div>` : ''}</div>`;
const pieces = {
  'h-sl': heading('Snakes &amp; Ladders', 'Every roll lands on the board for the whole clan'),
  'h-race': heading('Skill race', 'Standings move with every Wise Old Man sync'),
  'h-drop': heading('Drop race', 'Kill counts ranked live, no screenshots'),
  'h-bingo': heading('Clan bingo', 'The player claims, the host approves'),
  'h-create': heading('Create an event', 'From a template to an invite link'),
  'h-push': heading('Team push', 'Your phone knows when your team scores', 'center'),
  'tag-player': `<div class="tag"><i></i>Player view</div>`,
  'tag-host': `<div class="tag"><i></i>Host view</div>`,
  'intro-title': card("Your clan's next event,<br>start to finish", 'Bingo, Snakes &amp; Ladders and races, tracked for you.', 'Free · osrs-events.com'),
  'intro-end': card('Free for your clan', 'Bingo, Snakes &amp; Ladders and races', 'osrs-events.com'),
  'intro-1': step(1, 'Pick a template', 'Skill of the Week, Boss of the Month, bingo, Snakes &amp; Ladders, or start from scratch.'),
  'intro-2': step(2, 'Share the invite link', 'Post it in your clan Discord. Players join from the link.'),
  'intro-3': step(3, 'Players claim, you check', 'A screenshot for the host, or the RuneLite plugin claims it for them.'),
  'intro-4': step(4, 'Everyone follows along live', 'Boards and standings update by themselves.'),
  'setup-title': card('Set up the<br>RuneLite plugin', 'Five steps, about a minute.'),
  'setup-end': card('Free for your clan', 'The RuneLite plugin for clan events', 'osrs-events.com'),
  'setup-1': step(1, 'Install it from the Plugin Hub', 'Search for OSRS Events in RuneLite and press Install.'),
  'setup-2': step(2, 'Make a code on the site', 'Settings, then RuneLite plugin. The code is shown once.'),
  'setup-3': step(3, 'Paste it into the plugin', 'Plugin code, in the plugin&rsquo;s settings.'),
  'setup-4': step(4, 'Turn on Send completions', 'The chat confirms it is connected.'),
  'setup-5': step(5, 'Play', 'A kill that matches a square is claimed for you.'),
};
const only = process.argv.slice(2);
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 });
for (const [name, html] of Object.entries(pieces)) {
  if (only.length && !only.includes(name)) continue;
  await p.setContent(`<html><head>${head}</head><body><div class="wrap">${html}</div></body></html>`);
  await p.waitForLoadState('networkidle'); await p.evaluate(() => document.fonts.ready);
  await p.locator('.wrap > *').first().screenshot({ path: path.join(HERE, name + '.png'), omitBackground: true });
}
await b.close();
console.log('ok');
