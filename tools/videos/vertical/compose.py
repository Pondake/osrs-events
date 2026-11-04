# Vertical 1080x1920 cuts of the S&L and bingo loops for sharing.
import importlib.util, io, os, subprocess, sys, bisect
from PIL import Image, ImageDraw, ImageFont
HERE = os.path.dirname(os.path.abspath(__file__))
SCR = os.path.dirname(HERE)
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS = 30
FONT = ImageFont.truetype('C:/Windows/Fonts/segoeuib.ttf', 34)

def load(dirname):
    spec = importlib.util.spec_from_file_location(dirname, os.path.join(SCR, dirname, 'frames.py'))
    m = importlib.util.module_from_spec(spec); spec.loader.exec_module(m); return m

def pill(im, text, x, y):
    d = ImageDraw.Draw(im)
    w = d.textlength(text, font=FONT) + 44
    d.rounded_rectangle((x, y, x + w, y + 58), radius=29, fill=(245, 166, 35))
    d.text((x + w / 2, y + 29), text, font=FONT, fill=(28, 25, 23), anchor='mm')

def sl():
    m = load('sl-video')
    bands = Image.open(os.path.join(HERE, 'bands-sl.png'))
    def frame(t):
        c = Image.new('RGB', (1080, 1920)); c.paste(m.at(t), (0, 420)); c.paste(bands, (0, 0), bands); return c
    return frame, 0.3, 19.6, 0.7

def bingo():
    m = load('bingo')
    bands = Image.open(os.path.join(HERE, 'bands-bingo.png'))
    cache = {}
    def side(s, t):
        ts, files = m.SIDES[s]; i = max(0, bisect.bisect_right(ts, t) - 1)
        if (s, i) not in cache:
            if len(cache) > 8: cache.clear()
            cache[(s, i)] = Image.open(files[i]).convert('RGB').resize((1080, 1404), Image.LANCZOS)
        return cache[(s, i)]
    # Player claims, host approves, player sees the line. Short crossfades between.
    cuts = [('player', 0.2, 5.0), ('host', 5.0, 8.75), ('player', 8.75, 12.2)]
    X = 0.25
    def pane(t):
        for k, (s, a, b) in enumerate(cuts):
            if t < b or k == len(cuts) - 1:
                im = side(s, t)
                if k > 0 and t < a + X:
                    im = Image.blend(side(cuts[k - 1][0], t), im, (t - a) / X)
                return im, s
    def frame(t):
        im, s = pane(t)
        c = Image.new('RGB', (1080, 1920)); c.paste(im, (0, 260)); c.paste(bands, (0, 0), bands)
        pill(c, 'Player' if s == 'player' else 'Host', 32, 260 + 1404 - 90)
        return c
    return frame, 0.2, 12.2, 0.5

def run(name, make, crf):
    frame, start, end, fade = make()
    src = os.path.join(HERE, name + '.mjpg')
    n = round((end - start) * FPS); first = frame(start).copy()
    with open(src, 'wb') as f:
        for k in range(n):
            t = start + k / FPS; im = frame(t); left = end - t
            if left < fade: im = Image.blend(im, first, 1 - left / fade)
            b = io.BytesIO(); im.save(b, 'JPEG', quality=95); f.write(b.getvalue())
    first.save(os.path.join(HERE, name + '-first.png'))
    for out, q in [(name + '-master.webm', 14), (name + '-9x16.webm', crf)]:
        subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + src,
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2',
            os.path.join(HERE, out)], check=True)
        print(out, os.path.getsize(os.path.join(HERE, out)))
    os.remove(src)

if __name__ == '__main__':
  for name in sys.argv[1:]:
    run(name, {'sl': sl, 'bingo': bingo}[name], 30)
