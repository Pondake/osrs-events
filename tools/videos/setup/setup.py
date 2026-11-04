# Plugin setup video: five steps from one raw recording, names and the code covered.
import io, os, subprocess, sys, glob
from PIL import Image, ImageDraw, ImageFilter
HERE = os.path.dirname(os.path.abspath(__file__))
FF = os.environ.get('FFMPEG') or glob.glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS, W, H, COL = 30, 1920, 1080, 690
WINDOWS = [('e', 52), ('c', 60), ('d', 106), ('b', 36), ('a', 4)]
card = {k: Image.open(os.path.join(HERE, k + '.png')).convert('RGB') for k in ('title', 'end', 'col1', 'col2', 'col3', 'col4', 'col5')}
_n = {w: len(os.listdir(os.path.join(HERE, 'raw-' + w))) for w, _ in WINDOWS}

def raw(t):
    for w, t0 in WINDOWS:
        if t >= t0 and (t - t0) * FPS < _n[w]:
            return Image.open(os.path.join(HERE, f'raw-{w}', f'f{min(round((t - t0) * FPS), _n[w] - 1):03d}.jpg')).convert('RGB')
    raise ValueError(t)

def redact(im, box, fill):
    """A solid bar, not a blur: a blurred name can still be read by someone who knows it."""
    ImageDraw.Draw(im).rounded_rectangle(box, radius=min(6, (box[3] - box[1]) // 2), fill=fill)

def stage(col, content):
    c = Image.new('RGB', (W, H), (12, 11, 10))
    c.paste(card[col], (0, 0))
    scale = min((W - COL - 60) / content.width, (H - 60) / content.height)
    content = content.resize((round(content.width * scale), round(content.height * scale)), Image.LANCZOS)
    c.paste(content, (COL + (W - COL - content.width) // 2, (H - content.height) // 2))
    return c

def callout(c, line):
    lw, lh = round(line.width * 1.5), round(line.height * 1.5)
    line = line.resize((lw, lh), Image.LANCZOS)
    box = Image.new('RGB', (lw + 20, lh + 20), (245, 166, 35)); box.paste(line, (10, 10))
    c.paste(box, (COL + (W - COL - box.width) // 2, H - box.height - 40))

def s1(t):
    return stage('col1', raw(t).crop((960, 0, 1920, 760)))

def s2(t):
    f = raw(t)
    redact(f, (640, 344, 1220, 386), (58, 58, 54))
    return stage('col2', f.crop((340, 60, 1360, 560)))

def s3(t):
    return stage('col3', raw(t).crop((1600, 40, 1920, 720)))

CONNECTED = None
def s4(t):
    global CONNECTED
    c = stage('col4', raw(t).crop((880, 120, 1920, 780)))
    if t >= 61.3:
        if CONNECTED is None:
            line = raw(62.4).crop((0, 965, 760, 991))
            redact(line, (235, 0, 442, 26), (150, 136, 112))
            CONNECTED = line
        callout(c, CONNECTED)
    return c

def s5(t):
    f = raw(t)
    c = stage('col5', f.crop((0, 0, 1600, 800)))
    if t >= 112.7:
        callout(c, raw(t).crop((0, 958, 560, 1008)))
    return c

def s6(t):
    return stage('col5', raw(t).crop((320, 300, 1120, 1080)))

# (builder, source start, source end, speed, holds) - holds are {source time: seconds} freezes
SEGMENTS = [
    (lambda t: card['title'], 0, 3.2, 1, {}),
    (s1, 5.0, 13.6, 1.0, {6.8: 1.6, 11.9: 1.4}),
    (s2, 38.5, 42.2, 1.0, {41.8: 1.8}),
    (s3, 44.6, 48.8, 1.0, {48.7: 1.4}),
    (s4, 57.0, 63.5, 1.0, {58.9: 1.2, 63.4: 2.4}),
    (s5, 110.2, 114.5, 1.0, {114.4: 2.4}),
    (s6, 116.3, 119.9, 1.0, {119.8: 0.8}),
    (lambda t: card['end'], 0, 3.2, 1, {}),
]
X = round(0.4 * FPS)

def times(a, e, sp, holds):
    out, t = [], a
    pending = sorted(holds.items())
    while t < e:
        out.append(t)
        while pending and t >= pending[0][0]:
            out += [t] * round(pending[0][1] * FPS)
            pending.pop(0)
        t += sp / FPS
    return out

def frames():
    seq = [(b, times(a, e, sp, h)) for b, a, e, sp, h in SEGMENTS]
    for si, (b, ts) in enumerate(seq):
        last, prev = None, None
        for k, t in enumerate(ts):
            if si > 0 and k < X:
                continue
            if t != last:
                prev, last = b(t), t
            im = prev
            if si + 1 < len(seq) and k >= len(ts) - X:
                nb, nts = seq[si + 1]
                im = Image.blend(im, nb(nts[0]), (k - (len(ts) - X)) / X)
            yield im

if __name__ == '__main__':
    if sys.argv[1:] == ['peek']:
        picks = [(s1, 6.5), (s1, 12.5), (s2, 41.0), (s3, 47.5), (s4, 58.8), (s4, 63.0), (s5, 114.5), (s6, 117.5)]
        sh = Image.new('RGB', (4 * 480, 2 * 270))
        for i, (b, t) in enumerate(picks): sh.paste(b(t).resize((480, 270)), ((i % 4) * 480, (i // 4) * 270))
        sh.save(os.path.join(HERE, 'peek-setup.png')); sys.exit()
    src = os.path.join(HERE, 'setup.mjpg'); n = 0
    with open(src, 'wb') as fh:
        for im in frames():
            if n == round(5 * FPS): im.save(os.path.join(HERE, 'poster.png'))
            b = io.BytesIO(); im.save(b, 'JPEG', quality=94); fh.write(b.getvalue()); n += 1
    print('seconds', n / FPS)
    for out, q in [('master.webm', 14), ('setup.webm', 32)]:
        subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + src,
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', os.path.join(HERE, out)], check=True)
        print(out, os.path.getsize(os.path.join(HERE, out)))
    os.remove(src)
