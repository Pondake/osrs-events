# 9:16 cut of the plugin demo: game on top, claim line, card below.
import io, os, subprocess, sys
from PIL import Image, ImageDraw
import compose as c
W, H = 1080, 1920
GH = round((c.GAME[3] - c.GAME[1]) * W / (c.GAME[2] - c.GAME[0]))
GY, CBAND, CY0, CWIN = 230, 120, None, 735
CY0 = GY + GH + CBAND
CW = round(850 * CWIN / 925)
CX = (1080 - CW) // 2

bands = Image.open(os.path.join(c.HERE, 'bands-plugin.png'))

def frame(w, s, claim, state, k, prev):
    src = c.raw(w, s)
    im = Image.new('RGB', (W, H), (12, 11, 10))
    im.paste(src.crop(c.GAME).resize((W, GH), Image.LANCZOS), (0, GY))
    since = s - claim; lag = 0.35
    t = c.card_time(state, since, lag)
    card = c.card_view(t, 25, 875, 925, (270, 450)).resize((CW, CWIN), Image.LANCZOS)
    im.paste(card, (CX, CY0))
    im.paste(bands, (0, 0), bands)
    d = ImageDraw.Draw(im)
    if since >= lag and since - lag < 1.2:
        a = 1 - (since - lag) / 1.2
        for r in range(6):
            if a > r / 6: d.rectangle((CX + r, CY0 + r, CX + CW - 1 - r, CY0 + CWIN - 1 - r), outline=(245, 166, 35))
    c.pill(d, 'In game', 110, GY + 16)
    c.pill(d, 'On the site', CX + 95, CY0 + 16)
    if since >= 0.15:
        ys = c.claimed_rows(src); cy = max(ys) if ys else 892
        lines = 2 if state == 3 else 1
        line = src.crop((85, cy - 14, 1020, cy + 13 + 27 * (lines - 1)))
        z = 1.08 * min(1.0, 0.8 + 0.2 * c.ease((since - 0.15) / 0.3))
        lw, lh = round(line.width * z), round(line.height * z)
        line = line.resize((lw, lh), Image.LANCZOS)
        x, y = (W - lw) // 2, GY + GH + (CBAND - lh) // 2
        alpha = min(1.0, (since - 0.15) / 0.2)
        box = Image.new('RGB', (lw + 20, lh + 20), (245, 166, 35)); box.paste(line, (10, 10))
        base = im.crop((x - 10, y - 10, x + lw + 10, y + lh + 10))
        im.paste(Image.blend(base, box, alpha), (x - 10, y - 10))
    return im

tl = c.timeline(); states = [0] + [sg[4] for sg in c.SEGS]
if sys.argv[1:] == ['peek']:
    ims = [frame(*tl[round(t * 30)], states[tl[round(t * 30)][4]]).resize((360, 640)) for t in (2.0, 4.5, 9.5, 17.0)]
    sh = Image.new('RGB', (1440, 640)); [sh.paste(x, (i * 360, 0)) for i, x in enumerate(ims)]; sh.save(os.path.join(c.HERE, 'vpeek.png')); sys.exit()
src = os.path.join(c.HERE, 'vcomposed.mjpg'); first = None; fade = round(0.6 * 30)
with open(src, 'wb') as f:
    for i, t in enumerate(tl):
        im = frame(*t, states[t[4]])
        if first is None: first = im.copy()
        if len(tl) - i < fade: im = Image.blend(im, first, 1 - (len(tl) - i) / fade)
        b = io.BytesIO(); im.save(b, 'JPEG', quality=95); f.write(b.getvalue())
for out, q in [('vmaster.webm', 14), ('plugin-9x16.webm', 30)]:
    subprocess.run([c.FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', '30', '-i', 'file:' + src,
        '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', os.path.join(c.HERE, out)], check=True)
    print(out, os.path.getsize(os.path.join(c.HERE, out)))
os.remove(src)
