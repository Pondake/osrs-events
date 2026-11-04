# Plugin demo, tight cut: game | card side by side, the claim line zoomed in.
import io, os, subprocess, sys
import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont
HERE = os.path.dirname(os.path.abspath(__file__))
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS = 30
W, H = 1920, 1080
GAME = (70, 30, 1065, 680)
PW = 800
GW = W - PW
GH = round((GAME[3] - GAME[1]) * GW / (GAME[2] - GAME[0]))
GY = 84
WINDOWS = {'a': 44, 'b': 88, 'c': 117}
# (window, source start, source end, claim time, card state after the claim)
SEGS = [('a', 53.4, 58.4, 56.93, 1), ('b', 96.4, 101.4, 99.87, 2), ('c', 125.2, 131.4, 128.67, 3)]
HOLD = 3.6
FONT = ImageFont.truetype('C:/Windows/Fonts/segoeuib.ttf', 30)

tpl = np.asarray(Image.open(os.path.join(HERE, 'raw-a/f400.jpg')).convert('L').crop((465, 716, 578, 744)), dtype=float)
tpl -= tpl.mean()

ctpl = np.asarray(Image.open(os.path.join(HERE, 'raw-a/f400.jpg')).convert('L').crop((240, 880, 330, 905)), dtype=float)
ctpl -= ctpl.mean()

def claimed_rows(im):
    g = np.asarray(im.convert('L'), dtype=float)
    out = []
    for cy in range(700, 900):
        c = g[cy - 12:cy + 13, 240:330]; c = c - c.mean()
        if (c * ctpl).sum() / np.sqrt((c * c).sum() * (ctpl * ctpl).sum() + 1e-9) > 0.75:
            out.append(cy)
    return [y for y in out if y + 1 not in out or y - 1 not in out][1::2] or out

def scrub(im):
    """Blur the account name wherever a 'Connected as …' line sits in the chat."""
    g = np.asarray(im.convert('L'), dtype=float)
    scores = []
    for cy in range(695, 905):
        c = g[cy - 14:cy + 14, 465:578]; c = c - c.mean()
        scores.append((c * tpl).sum() / np.sqrt((c * c).sum() * (tpl * tpl).sum() + 1e-9))
    for k, s in enumerate(scores):
        if s > 0.6 and s == max(scores[max(0, k - 6):k + 7]):
            cy = 695 + k
            box = (462, cy - 15, 582, cy + 14)
            im.paste(im.crop(box).filter(ImageFilter.GaussianBlur(9)), box[:2])
    return im

def raw(w, t):
    i = max(0, min(round((t - WINDOWS[w]) * FPS), len(os.listdir(os.path.join(HERE, 'raw-' + w))) - 1))
    return Image.open(os.path.join(HERE, f'raw-{w}', f'f{i:03d}.jpg')).convert('RGB')

import bisect, json
_cj = json.load(open(os.path.join(HERE, 'cardframes.json')))
_K = _cj.get('K', 1)
CARD_T = [(m['t'] - _cj['t0']) / _K for m in _cj['meta']]
CARD_F = [os.path.join(HERE, 'cardframes', m['file']) for m in _cj['meta']]
SHOWN = {m['n']: m['shown'] / _K for m in _cj['marks'] if 'n' in m}
_cc = {}

def card_raw(t):
    i = max(0, bisect.bisect_right(CARD_T, t) - 1)
    if i not in _cc:
        if len(_cc) > 6: _cc.clear()
        _cc[i] = Image.open(CARD_F[i]).convert('RGB')
    return _cc[i]

def card_time(state, since, lag=0.35):
    # Before the claim lands: the frame just before this state appeared. After: the live clip from there on.
    return SHOWN[state] - 0.15 + max(0.0, since - lag)

CH = round(2430 * PW / 1800)
CY = (H - CH) // 2

def pill(d, text, cx, y):
    w = d.textlength(text, font=FONT) + 40
    d.rounded_rectangle((cx - w / 2, y, cx + w / 2, y + 50), radius=25, fill=(245, 166, 35))
    d.text((cx, y + 25), text, font=FONT, fill=(28, 25, 23), anchor='mm')

def ease(x):
    x = max(0.0, min(1.0, x)); return 1 - (1 - x) ** 3

def timeline():
    out, t = [], 0.0
    for k, (w, a, b, claim, state) in enumerate(SEGS):
        n = round((b - a) * FPS)
        for i in range(n):
            s = a + i / FPS
            out.append((w, s, claim, state, k))
    w, a, b, claim, state = SEGS[-1]
    for i in range(round(HOLD * FPS)):
        out.append((w, b, claim, state, len(SEGS) - 1))
    return out

def frame(w, s, claim, state, k, prev_state):
    src = raw(w, s)
    canvas = Image.new('RGB', (W, H), (12, 11, 10))
    canvas.paste(src.crop(GAME).resize((GW, GH), Image.LANCZOS), (0, GY))
    since = s - claim
    lag = 0.35
    card = card_raw(card_time(state, since, lag)).resize((PW, CH), Image.LANCZOS)
    canvas.paste(card, (GW, CY))
    d = ImageDraw.Draw(canvas)
    if since >= lag and since - lag < 1.2:
        a = 1 - (since - lag) / 1.2
        for r in range(6):
            d.rectangle((GW + r, CY + r, W - 1 - r, CY + CH - 1 - r), outline=(245, 166, 35) if a > r / 6 else (12, 11, 10))
    pill(d, 'In game, with the plugin', GW / 2, 17)
    pill(d, 'On the site', GW + PW / 2, 17)
    # The claim line, lifted out of the chat and enlarged.
    if since >= 0.15:
        ys = claimed_rows(src)
        cy = max(ys) if ys else 892
        lines = 2 if state == 3 else 1
        line = src.crop((85, cy - 14, 1020, cy + 13 + 27 * (lines - 1)))
        z = 1.1 * min(1.0, 0.8 + 0.2 * ease((since - 0.15) / 0.3))
        lw, lh = round(line.width * z), round(line.height * z)
        line = line.resize((lw, lh), Image.LANCZOS)
        x, y = (GW - lw) // 2, GY + GH + (H - GY - GH - lh) // 2
        alpha = min(1.0, (since - 0.15) / 0.2)
        box = Image.new('RGB', (lw + 24, lh + 24), (245, 166, 35))
        box.paste(line, (12, 12))
        base = canvas.crop((x - 12, y - 12, x + lw + 12, y + lh + 12))
        canvas.paste(Image.blend(base, box, alpha), (x - 12, y - 12))
    return canvas

def main(name, crf):
    tl = timeline()
    src = os.path.join(HERE, 'composed.mjpg')
    first = None
    fade = round(0.6 * FPS)
    states = [0] + [s[4] for s in SEGS]
    with open(src, 'wb') as f:
        for idx, (w, s, claim, state, k) in enumerate(tl):
            im = frame(w, s, claim, state, k, states[k])
            if first is None:
                first = im.copy()
            left = len(tl) - idx
            if left < fade:
                im = Image.blend(im, first, 1 - left / fade)
            b = io.BytesIO(); im.save(b, 'JPEG', quality=95); f.write(b.getvalue())
    first.save(os.path.join(HERE, 'first.png'))
    frame(*tl[len(tl) - 10]).save(os.path.join(HERE, 'last.png')) if False else None
    for out, q in [('master.webm', 14), (name, crf)]:
        subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + src,
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2',
            os.path.join(HERE, out)], check=True)
        print(out, os.path.getsize(os.path.join(HERE, out)), len(tl) / FPS)

if __name__ == '__main__':
    if sys.argv[1:] == ['peek']:
        states = [0, 1, 2]
        tl = timeline()
        ims = []
        for t in (3.0, 4.2, 5.8, 9.5, 14.5, 17.5):
            w, s, claim, state, k = tl[min(len(tl) - 1, round(t * FPS))]
            ims.append(frame(w, s, claim, state, k, states[k]).resize((640, 360)))
        sheet = Image.new('RGB', (1920, 720))
        for i, im in enumerate(ims): sheet.paste(im, ((i % 3) * 640, (i // 3) * 360))
        sheet.save(os.path.join(HERE, 'peek.png'))
    else:
        main('plugin.webm', int(sys.argv[1]) if len(sys.argv) > 1 else 32)
