# Plugin demo in the shared style: 16:9 for the site, 9:16 for sharing.
import io
import os
import subprocess
import sys
sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'style'))
from PIL import Image
import compose as c
import style as st

LAG = 0.35


def chat_lines(src, state):
    ys = c.claimed_rows(src)
    cy = max(ys) if ys else 892
    n = 2 if state == 3 else 1
    return src.crop((85, cy - 14, 1020, cy + 13 + 27 * (n - 1)))


def glow_at(since):
    return max(0.0, 1 - (since - LAG) / 1.4) if since >= LAG else 0.0


def landscape(w, s, claim, state, k):
    W, H, M = 1920, 1080, 48
    cv = st.background(W, H)
    st.put(cv, 'brand', (M, 60))
    t16 = st.asset('title16')
    st.put(cv, 'title16', (W - M - t16.width, 48))
    gap = 28
    ph = round((W - 2 * M - gap) / (995 / 650 + 900 / 1215))
    gw, cw = round(ph * 995 / 650), W - 2 * M - gap - round(ph * 995 / 650)
    top = H - 40 - ph
    src = c.raw(w, s)
    since = s - claim
    st.panel(cv, src.crop(c.GAME).resize((gw, ph), Image.LANCZOS), (M, top))
    card = c.card_view(c.card_time(state, since, LAG), 0, 900, 1215, (0, 166)).resize((cw, ph), Image.LANCZOS)
    st.panel(cv, card, (M + gw + gap, top), glow=glow_at(since))
    tg = st.asset('tag-game')
    st.put(cv, 'tag-game', (M, top - tg.height - 14))
    st.put(cv, 'tag-site', (M + gw + gap, top - tg.height - 14))
    if since >= 0.15:
        st.callout(cv, chat_lines(src, state), M + gw / 2, top + ph - 28, (since - 0.15) / 0.3, gw - 80, anchor='bottom')
    return cv.convert('RGB')


def portrait(w, s, claim, state, k):
    W, H, M = 1080, 1920, 40
    cv = st.background(W, H)
    vh = st.asset('vhead')
    st.put(cv, 'vhead', ((W - vh.width) / 2, 60))
    tg = st.asset('tag-game-short')
    top = 60 + vh.height + 26 + tg.height + 12
    gw = W - 2 * M
    gh = round(gw * 650 / 995)
    src = c.raw(w, s)
    since = s - claim
    st.put(cv, 'tag-game-short', (M, top - tg.height - 12))
    st.panel(cv, src.crop(c.GAME).resize((gw, gh), Image.LANCZOS), (M, top))
    vf = st.asset('vfoot')
    ctop = top + gh + 30 + tg.height + 12
    ch = H - ctop - vf.height - 90
    cw = round(ch * 880 / 925)
    cx = (W - cw) // 2
    card = c.card_view(c.card_time(state, since, LAG), 10, 890, 925, (292, 418)).resize((cw, ch), Image.LANCZOS)
    st.panel(cv, card, (cx, ctop), glow=glow_at(since))
    st.put(cv, 'tag-site', (cx, ctop - tg.height - 12))
    if since >= 0.15:
        st.callout(cv, chat_lines(src, state), W / 2, top + gh - 20, (since - 0.15) / 0.3, gw - 40, anchor='bottom', scale=1.0)
    st.put(cv, 'vfoot', ((W - vf.width) / 2, H - vf.height - 50))
    return cv.convert('RGB')


MID_HOLD = 2.0


def frames():
    # The game pauses after each claim so the chat line can be read; the card keeps playing.
    out = []
    for k, (w, a, b, claim, state) in enumerate(c.SEGS):
        out += [(w, a + i / 30, claim, state, k) for i in range(round((b - a) * 30))]
        hold = c.HOLD if k == len(c.SEGS) - 1 else MID_HOLD
        out += [(w, b, claim - (i + 1) / 30, state, k) for i in range(round(hold * 30))]
    return out


def render(fn, out, crf):
    tl = frames()
    src = os.path.join(c.HERE, out + '.mjpg')
    first = None
    fade = round(0.6 * 30)
    with open(src, 'wb') as f:
        for i, t in enumerate(tl):
            im = fn(*t)
            if first is None:
                first = im.copy()
                first.save(os.path.join(c.HERE, out + '-poster.png'))
            if len(tl) - i < fade:
                im = Image.blend(im, first, 1 - (len(tl) - i) / fade)
            b = io.BytesIO()
            im.save(b, 'JPEG', quality=95)
            f.write(b.getvalue())
    for name, q in [(out + '-master.webm', 14), (out + '.webm', crf)]:
        subprocess.run([c.FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', '30', '-i', 'file:' + src,
                        '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(q), '-pix_fmt', 'yuv420p', '-row-mt', '1',
                        '-deadline', 'good', '-cpu-used', '2', os.path.join(c.HERE, name)], check=True)
        print(name, os.path.getsize(os.path.join(c.HERE, name)))
    os.remove(src)


if __name__ == '__main__':
    tl = frames()
    if sys.argv[1] == 'peek':
        for fn, name in [(landscape, 'peek16'), (portrait, 'peek9')]:
            for t in sys.argv[2:]:
                fn(*tl[round(float(t) * 30)]).save(os.path.join(c.HERE, f'{name}-{t}.png'))
    else:
        render(landscape, 'styled16', 32)
        render(portrait, 'styled9', 30)
