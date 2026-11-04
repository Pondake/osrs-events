# Homepage intro: create, share, claim, live — cut from the guide clips.
import importlib.util, io, os, subprocess, sys
from PIL import Image
HERE = os.path.dirname(os.path.abspath(__file__))
SCR = os.path.dirname(HERE)
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS, W, H, TOP = 30, 1920, 1080, 170
CH = H

def load(d):
    spec = importlib.util.spec_from_file_location('f_' + d.replace('-', '_'), os.path.join(SCR, d, 'frames.py'))
    m = importlib.util.module_from_spec(spec); spec.loader.exec_module(m); return m
create, race, sl = load('create'), load('race'), load('sl-video')
sys.path.insert(0, os.path.join(SCR, 'vertical'))
import bingo_seq
img = {k: Image.open(os.path.join(HERE, k + '.png')).convert('RGB') for k in ('title', 'end', 'col1', 'col2', 'col3', 'col4')}

def fit(im, h=CH):
    return im.resize((round(im.width * h / im.height), h), Image.LANCZOS)

COL = 690

def stage(col, content):
    c = Image.new('RGB', (W, H), (12, 11, 10))
    c.paste(img[col], (0, 0))
    if content.width > W - COL:
        content = content.resize((W - COL, round(content.height * (W - COL) / content.width)), Image.LANCZOS)
    c.paste(content, (COL + (W - COL - content.width) // 2, (H - content.height) // 2))
    return c

MOVES = [(2.99, 3.63), (5.99, 6.64), (8.82, 9.49), (11.83, 12.51)]
RSEGS = [(0.3, 1.4)] + [(s - 0.2, e + (1.5 if k == len(MOVES) - 1 else 0.9)) for k, (s, e) in enumerate(MOVES)]
RACE_T = [a + i / FPS for a, b in RSEGS for i in range(round((b - a) * FPS))]

# Each section: (frames, builder(k) -> Image)
SL_N = round(5.0 * FPS)
SECTIONS = [
    (round(2.6 * FPS), lambda k: img['title']),
    (round(20.0 / 1.6 * FPS), lambda k: (lambda t: stage('col1' if t < 14.6 else 'col2', fit(create.at(t))))(0.5 + k * 1.6 / FPS)),
    (round(12.0 / 1.25 * FPS), lambda k: stage('col3', fit(bingo_seq.frame(0.2 + k * 1.25 / FPS)))),
    (SL_N, lambda k: stage('col4', fit(sl.at(5.5 + k / FPS)))),
    (len(RACE_T), lambda k: stage('col4', fit(race.at(RACE_T[k])))),
    (round(3.2 * FPS), lambda k: img['end']),
]
X = round(0.4 * FPS)

def frames():
    for si, (n, f) in enumerate(SECTIONS):
        for k in range(n):
            im = f(k)
            if si + 1 < len(SECTIONS) and k >= n - X:
                im = Image.blend(im, SECTIONS[si + 1][1](0), (k - (n - X)) / X)
            elif si > 0 and k < X:
                continue
            yield im

if __name__ == '__main__':
    if sys.argv[1:] == ['peek']:
        allf = []
        for si, (n, f) in enumerate(SECTIONS):
            allf += [f(k) for k in (0, n // 2, n - 1)] if si in (1, 2, 3) else [f(0)]
        sh = Image.new('RGB', (4 * 480, 3 * 270))
        for i, im in enumerate(allf[:12]): sh.paste(im.resize((480, 270)), ((i % 4) * 480, (i // 4) * 270))
        sh.save(os.path.join(HERE, 'peek.png')); sys.exit()
    src = os.path.join(HERE, 'intro.mjpg'); n = 0
    with open(src, 'wb') as fh:
        for im in frames():
            if n == round(6.5 * FPS): im.save(os.path.join(HERE, 'poster.png'))
            b = io.BytesIO(); im.save(b, 'JPEG', quality=94); fh.write(b.getvalue()); n += 1
    print('seconds', n / FPS)
    for out, q in [('master.webm', 14), ('intro.webm', int(sys.argv[1]) if sys.argv[1:] else 32)]:
        subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + src,
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', os.path.join(HERE, out)], check=True)
        print(out, os.path.getsize(os.path.join(HERE, out)))
    os.remove(src)
