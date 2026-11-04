# Every demo video in the shared style. python restyle.py <name> [peek t1 t2 ...]
import importlib.util, io, os, subprocess, sys
from PIL import Image
import style as st

HERE = os.path.dirname(os.path.abspath(__file__))
SCR = os.path.dirname(HERE)
OUT = os.path.join(SCR, 'styled')
os.makedirs(OUT, exist_ok=True)
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS = 30


def load(d, name='frames'):
    sys.path.insert(0, os.path.join(SCR, d))
    spec = importlib.util.spec_from_file_location(f'm_{d}_{name}'.replace('-', '_'), os.path.join(SCR, d, name + '.py'))
    m = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(m)
    sys.path.pop(0)
    return m


def span(a, b):
    return [a + k / FPS for k in range(round((b - a) * FPS))]


def fast(moves):
    segs = [(0.3, 1.4)] + [(s - 0.2, e + (1.5 if k == len(moves) - 1 else 0.9)) for k, (s, e) in enumerate(moves)]
    return [a + i / FPS for a, b in segs for i in range(round((b - a) * FPS))]


def simple(d, heading, ts, fade, poster=None, at='at'):
    m = load(d)
    f = getattr(m, at)
    return [lambda t=t: st.clip(f(t), heading) for t in ts], fade, poster


def bingo_side(b, t, X=0.25):
    for k, (s, a, e) in enumerate(b.cuts):
        if t < e or k == len(b.cuts) - 1:
            im, alpha, prev = b.side(s, t), 1.0, None
            if k > 0 and t < a + X:
                prev = b.cuts[k - 1][0]
                im = Image.blend(b.side(prev, t), im, (t - a) / X)
                alpha = (t - a) / X
            return im, s, prev, alpha


def seq():
    sys.path.insert(0, os.path.join(SCR, 'vertical'))
    b = load('vertical', 'bingo_seq')

    def frame(t):
        im, s, prev, alpha = bingo_side(b, t)
        cv = st.clip(im, 'h-bingo', tag='tag-' + s, tag_alpha=alpha)
        if prev and alpha < 1:
            over = st.clip(im, 'h-bingo', tag='tag-' + prev, tag_alpha=1 - alpha)
            cv = Image.blend(over, cv, alpha)
        return cv
    return [lambda t=t: frame(t) for t in span(0.2, 12.2)], 0.5, 10.0 - 0.2


def side_stage(prefix, cols):
    def stage(col, content):
        W, H, M = 1920, 1080, 60
        cv = st.background(W, H)
        st.put(cv, 'brand', (70, 56))
        step = st.asset(cols[col])
        st.put(cv, cols[col], (70, (H - step.height) / 2 + 20))
        tag = stage.__dict__.pop('tag', None)
        x0, x1, y0, y1 = 650, W - M, 56 if tag is None else 112, H - M
        k = min((x1 - x0) / content.width, (y1 - y0) / content.height)
        content = content.resize((round(content.width * k), round(content.height * k)), Image.LANCZOS)
        box = (x0 + (x1 - x0 - content.width) // 2, y0 + (y1 - y0 - content.height) // 2)
        st.panel(cv, content, box)
        if tag:
            s, prev, alpha = tag
            ty = box[1] - st.asset('tag-player').height - 12
            st.put(cv, 'tag-' + s, (box[0], ty), alpha)
            if prev and alpha < 1:
                st.put(cv, 'tag-' + prev, (box[0], ty), 1 - alpha)
        stage.last = (box, content.size)
        return cv
    return stage


def card(name):
    cv = st.background(1920, 1080)
    a = st.asset(name)
    st.put(cv, name, ((1920 - a.width) / 2, (1080 - a.height) / 2))
    return cv.convert('RGB')


def intro():
    m = load('intro', 'intro')
    s = side_stage('intro', {f'col{i}': f'intro-{i}' for i in range(1, 5)})
    m.stage = lambda col, content: s(col, content).convert('RGB')

    def bframe(t):
        im, side, prev, alpha = bingo_side(m.bingo_seq, t)
        s.tag = (side, prev, alpha)
        return im
    m.bingo_seq.frame = bframe
    m.img['title'] = card('intro-title')
    m.img['end'] = card('intro-end')
    return m.frames, None, 6.5


def setup():
    m = load('setup', 'setup')
    stage = side_stage('setup', {f'col{i}': f'setup-{i}' for i in range(1, 6)})
    m.card['title'] = card('setup-title')
    m.card['end'] = card('setup-end')

    def with_callout(col, crop, t, line, since):
        cv = stage(col, crop)
        if line is not None:
            (x, y), (w, h) = stage.last
            st.callout(cv, line, x + w / 2, y + h - 24, since / 0.3, w - 60, anchor='bottom', scale=1.5)
        return cv.convert('RGB')

    m.stage = lambda col, content: stage(col, content).convert('RGB')
    connected = {}

    def s4(t):
        if t >= 61.3 and 'l' not in connected:
            line = m.raw(62.4).crop((0, 965, 760, 991))
            m.redact(line, (235, 0, 442, 26), (150, 136, 112))
            connected['l'] = line
        return with_callout('col4', m.raw(t).crop((880, 120, 1920, 780)), t, connected.get('l') if t >= 61.3 else None, t - 61.3)

    def s5(t):
        f = m.raw(t)
        line = m.raw(t).crop((0, 958, 560, 1008)) if t >= 112.7 else None
        return with_callout('col5', f.crop((0, 0, 1600, 800)), t, line, t - 112.7)

    segs = list(m.SEGMENTS)
    segs[4] = (s4,) + segs[4][1:]
    segs[5] = (s5,) + segs[5][1:]
    m.SEGMENTS[:] = segs
    return m.frames, None, 5.0


CLIPS = {
    'sl-board': lambda: simple('sl-video', 'h-sl', span(0.3, 19.6), 0.7, 0.0),
    'skill-race': lambda: simple('race', 'h-race', fast([(2.99, 3.63), (5.99, 6.64), (8.82, 9.49), (11.83, 12.51)]), 0.6, 0.0),
    'drop-race': lambda: simple('drop', 'h-drop', fast([(3.03, 3.73), (6.03, 6.73), (8.87, 9.57), (11.87, 12.57)]), 0.6, 0.0),
    'create-event': lambda: simple('create', 'h-create', span(0.3, 21.0), 0.6, 0.0),
    'team-push': lambda: simple('push', 'h-push', span(0.3, 7.0), 0.5, 2.7),
    'bingo-review': seq,
    'intro': intro,
    'plugin-setup': setup,
}


def stream(name):
    fs, fade, poster = CLIPS[name]()
    if callable(fs):
        for im in fs():
            yield im
        return
    first = fs[0]()
    n = round(fade * FPS)
    for i, f in enumerate(fs):
        im = f()
        if len(fs) - i < n:
            im = Image.blend(im, first, 1 - (len(fs) - i) / n)
        yield im


def poster_index(name):
    return round(CLIPS[name]()[2] * FPS)


def render(name, crf=32):
    src = os.path.join(OUT, name + '.mjpg')
    pi = poster_index(name)
    n = 0
    with open(src, 'wb') as f:
        for im in stream(name):
            if n == pi:
                im.save(os.path.join(OUT, name + '-poster.png'))
            b = io.BytesIO()
            im.save(b, 'JPEG', quality=95)
            f.write(b.getvalue())
            n += 1
    print(name, 'seconds', n / FPS, im.size)
    for out, q in [(name + '-master.webm', 14), (name + '.webm', crf)]:
        subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + src,
                        '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1',
                        '-deadline', 'good', '-cpu-used', '2', os.path.join(OUT, out)], check=True)
        print(out, os.path.getsize(os.path.join(OUT, out)))
    os.remove(src)


def peek(name, times):
    want = {round(float(t) * FPS): t for t in times}
    for i, im in enumerate(stream(name)):
        if i in want:
            im.save(os.path.join(OUT, f'peek-{name}-{want[i]}.png'))
        if i >= max(want):
            break


def sheet(name, every=15, cols=8):
    ims = [im.resize((im.width // 5, im.height // 5)) for i, im in enumerate(stream(name)) if i % every == 0]
    w, h = ims[0].size
    rows = (len(ims) + cols - 1) // cols
    sh = Image.new('RGB', (cols * w, rows * h))
    for i, im in enumerate(ims):
        sh.paste(im, ((i % cols) * w, (i // cols) * h))
    sh.save(os.path.join(OUT, f'sheet-{name}.jpg'), quality=85)


if __name__ == '__main__':
    name, rest = sys.argv[1], sys.argv[2:]
    if rest and rest[0] == 'peek':
        peek(name, rest[1:])
    elif rest and rest[0] == 'sheet':
        sheet(name, int(rest[1]) if rest[1:] else 15)
    else:
        render(name, int(rest[0]) if rest else 32)
