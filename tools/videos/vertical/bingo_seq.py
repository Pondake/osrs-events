# Bingo review clip for the guide: player, host, player — one page at a time, full width.
import io, os, subprocess, bisect
from PIL import Image, ImageDraw
import compose as c
W, CROP_H, BAND = 1200, 1230, 70
m = c.load('bingo')
cache = {}
def side(s, t):
    ts, files = m.SIDES[s]; i = max(0, bisect.bisect_right(ts, t) - 1)
    if (s, i) not in cache:
        if len(cache) > 8: cache.clear()
        cache[(s, i)] = Image.open(files[i]).convert('RGB').crop((0, 0, W, CROP_H))
    return cache[(s, i)]
cuts = [('player', 0.2, 5.0), ('host', 5.0, 8.75), ('player', 8.75, 12.2)]
X = 0.25
def frame(t):
    for k, (s, a, b) in enumerate(cuts):
        if t < b or k == len(cuts) - 1:
            im = side(s, t)
            if k > 0 and t < a + X:
                im = Image.blend(side(cuts[k - 1][0], t), im, (t - a) / X)
            break
    canvas = Image.new('RGB', (W, CROP_H + BAND), (12, 11, 10))
    canvas.paste(im, (0, BAND))
    d = ImageDraw.Draw(canvas)
    for name, x in (('Player', W / 2 - 110), ('Host', W / 2 + 110)):
        on = (name.lower() == s)
        w = d.textlength(name, font=c.FONT) + 44
        d.rounded_rectangle((x - w / 2, 8, x + w / 2, 62), radius=27, fill=(245, 166, 35) if on else (41, 37, 33))
        d.text((x, 35), name, font=c.FONT, fill=(28, 25, 23) if on else (168, 162, 158), anchor='mm')
    return canvas
if __name__ == '__main__':
    start, end, fade = 0.2, 12.2, 0.5
    src = os.path.join(c.HERE, 'seq.mjpg'); n = round((end - start) * 30); first = frame(start).copy()
    with open(src, 'wb') as f:
        for k in range(n):
            t = start + k / 30; im = frame(t); left = end - t
            if left < fade: im = Image.blend(im, first, 1 - left / fade)
            b = io.BytesIO(); im.save(b, 'JPEG', quality=95); f.write(b.getvalue())
    frame(10.0).save(os.path.join(c.HERE, 'seq-poster.png'))
    for out, q in [('seq-master.webm', 14), ('bingo-seq.webm', 32)]:
        subprocess.run([c.FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', '30', '-i', 'file:' + src,
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', os.path.join(c.HERE, out)], check=True)
        print(out, os.path.getsize(os.path.join(c.HERE, out)))
    os.remove(src)
