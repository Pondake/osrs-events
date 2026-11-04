// Renders original finish jingles with sampled GM instruments (FluidR3_GM, CC BY 3.0).
import { createRequire } from 'node:module';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
const REPO = path.resolve(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1'), '../../..');
const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\//, '');
const SF = path.join(HERE, '..', 'sf');
const { chromium } = createRequire(REPO + '/package.json')('@playwright/test');
const NAMES = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];
const nm = (m) => NAMES[m % 12] + (Math.floor(m / 12) - 1);
const N = (s) => { const r = /^([A-G]b?)(-?\d)$/.exec(s); return NAMES.indexOf(r[1]) + 12 * (Number(r[2]) + 1); };

// Each score: list of [instrument, note, start s, length s, velocity]
const chord = (inst, notes, t, len, v) => notes.map((n) => [inst, n, t, len, v]);
const SCORES = {
  triumph: [
    ['trumpet', 'G4', 0.00, 0.16, 0.8], ['trumpet', 'C5', 0.16, 0.16, 0.85], ['trumpet', 'E5', 0.32, 0.16, 0.9],
    ['trumpet', 'G5', 0.48, 1.25, 1.0], ['trumpet', 'E5', 0.48, 1.25, 0.7],
    ...chord('french_horn', ['C4', 'E4', 'G4'], 0.48, 1.35, 0.7), ['trombone', 'C3', 0.48, 1.35, 0.8],
    ['timpani', 'G2', 0.00, 0.4, 0.5], ['timpani', 'C2', 0.48, 1.4, 1.0],
    ...chord('string_ensemble_1', ['C4', 'G4', 'C5'], 0.48, 1.5, 0.5),
  ],
  quest: [
    ...['C3', 'G3', 'C4', 'E4', 'G4', 'C5', 'E5', 'G5'].map((n, i) => ['orchestral_harp', n, i * 0.07, 1.4, 0.8]),
    ['french_horn', 'E4', 0.10, 0.28, 0.7], ['french_horn', 'F4', 0.38, 0.28, 0.75], ['french_horn', 'G4', 0.66, 1.3, 0.85],
    ...chord('string_ensemble_1', ['C3', 'G3', 'E4'], 0.66, 1.4, 0.55),
    ['glockenspiel', 'G6', 0.66, 1.0, 0.45], ['glockenspiel', 'C7', 0.86, 1.0, 0.4],
    ['timpani', 'C2', 0.66, 1.2, 0.8],
  ],
  medal: [
    ['trumpet', 'C5', 0.00, 0.14, 0.85], ['trumpet', 'E5', 0.00, 0.14, 0.75],
    ['trumpet', 'D5', 0.16, 0.14, 0.85], ['trumpet', 'F5', 0.16, 0.14, 0.75],
    ['trumpet', 'E5', 0.32, 1.2, 1.0], ['trumpet', 'G5', 0.32, 1.2, 0.85],
    ['tubular_bells', 'C5', 0.32, 1.8, 0.6],
    ...chord('french_horn', ['C4', 'G4'], 0.32, 1.3, 0.7), ['trombone', 'C3', 0.32, 1.3, 0.75],
    ['timpani', 'C2', 0.32, 1.3, 1.0],
  ],
};
SCORES.boss = [
    ...[0, 0.06, 0.12, 0.18, 0.24, 0.30, 0.36].map((t, k) => ['timpani', 'C2', t, 0.12, 0.3 + k * 0.07]),
    ['trombone', 'C3', 0.42, 0.32, 0.95], ['trombone', 'G3', 0.42, 0.32, 0.85],
    ...chord('french_horn', ['Eb4', 'G4'], 0.42, 0.32, 0.85),
    ...chord('string_ensemble_1', ['C3', 'Eb4', 'G4'], 0.42, 0.34, 0.7),
    ['timpani', 'C2', 0.42, 0.5, 1.0],
    ['trumpet', 'G4', 0.82, 0.14, 0.85], ['trumpet', 'B4', 0.97, 0.14, 0.9],
    ['trumpet', 'C5', 1.12, 1.25, 1.0], ['trumpet', 'E5', 1.12, 1.25, 0.75],
    ...chord('french_horn', ['C4', 'E4', 'G4'], 1.12, 1.3, 0.7), ['trombone', 'C3', 1.12, 1.3, 0.8],
    ['timpani', 'C2', 1.12, 1.3, 1.0],
];
for (const k of Object.keys(SCORES)) if (process.argv[2] && k !== process.argv[2]) delete SCORES[k];
const insts = [...new Set(Object.values(SCORES).flat().map((e) => e[0]))];
const fonts = {};
for (const i of insts) {
  const txt = readFileSync(path.join(SF, i + '.js'), 'utf8');
  const obj = txt.slice(txt.indexOf('{', txt.indexOf('MIDI.Soundfont.' + i)), txt.lastIndexOf('}') + 1);
  fonts[i] = JSON.parse(obj.replace(/,\s*}$/, '}'));
}
const b = await chromium.launch();
const p = await b.newPage(); await p.setContent('<html></html>');
for (const [name, score] of Object.entries(SCORES)) {
  // keep only the samples this score needs
  const need = {};
  for (const [i, n] of score) (need[i] ??= {})[n] = fonts[i][n];
  const res = await p.evaluate(async ({ score, need }) => {
    const rate = 44100, secs = 3.0;
    const ctx = new OfflineAudioContext(2, rate * secs, rate);
    const buffers = {};
    for (const [i, notes] of Object.entries(need)) for (const [n, uri] of Object.entries(notes)) {
      const bin = Uint8Array.from(atob(uri.split(',')[1]), (c) => c.charCodeAt(0));
      buffers[i + n] = await ctx.decodeAudioData(bin.buffer);
    }
    const dry = ctx.createGain(); dry.gain.value = 0.8; dry.connect(ctx.destination);
    const rev = ctx.createConvolver();
    const ir = ctx.createBuffer(2, rate * 1.8, rate);
    for (let c = 0; c < 2; c++) { const d = ir.getChannelData(c); for (let k = 0; k < d.length; k++) d[k] = (Math.random() * 2 - 1) * (1 - k / d.length) ** 3; }
    rev.buffer = ir; const wet = ctx.createGain(); wet.gain.value = 0.28; rev.connect(wet).connect(ctx.destination);
    for (const [i, n, t, len, v] of score) {
      const src = ctx.createBufferSource(); src.buffer = buffers[i + n];
      const g = ctx.createGain();
      g.gain.setValueAtTime(v, t); g.gain.setValueAtTime(v, t + len); g.gain.linearRampToValueAtTime(0, t + len + 0.25);
      src.connect(g); g.connect(dry); g.connect(rev);
      src.start(t); src.stop(t + len + 0.3);
    }
    const buf = await ctx.startRendering();
    const L = buf.getChannelData(0), R = buf.getChannelData(1);
    let peak = 0; for (let k = 0; k < L.length; k++) peak = Math.max(peak, Math.abs(L[k]), Math.abs(R[k]));
    const gain = 0.8 / peak, n = L.length, out = new DataView(new ArrayBuffer(44 + n * 4));
    const w = (o, s) => [...s].forEach((c, k) => out.setUint8(o + k, c.charCodeAt(0)));
    w(0, 'RIFF'); out.setUint32(4, 36 + n * 4, true); w(8, 'WAVE'); w(12, 'fmt ');
    out.setUint32(16, 16, true); out.setUint16(20, 1, true); out.setUint16(22, 2, true); out.setUint32(24, rate, true);
    out.setUint32(28, rate * 4, true); out.setUint16(32, 4, true); out.setUint16(34, 16, true); w(36, 'data'); out.setUint32(40, n * 4, true);
    for (let k = 0; k < n; k++) { out.setInt16(44 + k * 4, Math.max(-1, Math.min(1, L[k] * gain)) * 32767, true); out.setInt16(46 + k * 4, Math.max(-1, Math.min(1, R[k] * gain)) * 32767, true); }
    let s = ''; const bytes = new Uint8Array(out.buffer); for (let k = 0; k < bytes.length; k += 8192) s += String.fromCharCode(...bytes.subarray(k, k + 8192));
    return btoa(s);
  }, { score, need });
  writeFileSync(path.join(HERE, `osrs-${name}.wav`), Buffer.from(res, 'base64'));
  console.log(name, 'ok');
}
await b.close();
