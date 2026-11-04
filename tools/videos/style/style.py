# Shared look for the demo videos: background, panels, tags, the chat callout.
import os
from PIL import Image, ImageDraw, ImageFilter
HERE = os.path.dirname(os.path.abspath(__file__))
AMBER = (251, 191, 36)
PANEL = (28, 25, 23)
_assets = {}


def asset(name):
    if name not in _assets:
        _assets[name] = Image.open(os.path.join(HERE, name + '.png')).convert('RGBA')
    return _assets[name]


def ease(x):
    x = max(0.0, min(1.0, x))
    return 1 - (1 - x) ** 3


_bg = {}


def background(w, h):
    if (w, h) not in _bg:
        base = Image.new('RGB', (w, h), (13, 11, 9))
        glow = Image.new('L', (w, h), 0)
        ImageDraw.Draw(glow).ellipse((-w * 0.2, -h * 0.55, w * 1.2, h * 0.45), fill=70)
        glow = glow.filter(ImageFilter.GaussianBlur(min(w, h) * 0.18))
        warm = Image.new('RGB', (w, h), (58, 40, 14))
        _bg[(w, h)] = Image.composite(warm, base, glow).convert('RGBA')
    return _bg[(w, h)].copy()


def _mask(size, r):
    m = Image.new('L', size, 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, size[0] - 1, size[1] - 1), radius=r, fill=255)
    return m


_shadows = {}


def _shadow(size, r, spread=22, alpha=150):
    key = (size, r, spread, alpha)
    if key not in _shadows:
        w, h = size
        s = Image.new('L', (w + spread * 4, h + spread * 4), 0)
        ImageDraw.Draw(s).rounded_rectangle((spread * 2, spread * 2, spread * 2 + w, spread * 2 + h), radius=r, fill=alpha)
        _shadows[key] = s.filter(ImageFilter.GaussianBlur(spread))
    return _shadows[key]


def panel(canvas, im, xy, r=18, glow=0.0):
    x, y = int(xy[0]), int(xy[1])
    w, h = im.size
    sh = _shadow((w, h), r)
    black = Image.new('RGBA', sh.size, (0, 0, 0, 0))
    black.putalpha(sh)
    canvas.alpha_composite(black, (x - 44, y - 44 + 12))
    if glow > 0:
        g = _shadow((w, h), r, spread=18, alpha=int(230 * glow))
        amber = Image.new('RGBA', g.size, AMBER + (0,))
        amber.putalpha(g)
        canvas.alpha_composite(amber, (x - 36, y - 36))
    tile = im.convert('RGBA')
    tile.putalpha(_mask((w, h), r))
    canvas.alpha_composite(tile, (x, y))
    edge = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    ImageDraw.Draw(edge).rounded_rectangle((0, 0, w - 1, h - 1), radius=r,
                                           outline=(255, 255, 255, 34 + int(150 * glow)), width=1 + int(glow * 2))
    canvas.alpha_composite(edge, (x, y))


def put(canvas, name, xy, alpha=1.0):
    a = asset(name)
    if alpha < 1:
        a = a.copy()
        a.putalpha(a.getchannel('A').point(lambda v: int(v * alpha)))
    canvas.alpha_composite(a, (int(xy[0]), int(xy[1])))


def callout(canvas, lines, center_x, y, progress, max_width, anchor='top', scale=1.25):
    if progress <= 0:
        return
    k = min(scale, (max_width - 48) / lines.width)
    lines = lines.resize((round(lines.width * k), round(lines.height * k)), Image.LANCZOS)
    label = asset('chat-label')
    pad = 18
    w = lines.width + pad * 2
    h = pad + label.height + 10 + lines.height + pad
    card = Image.new('RGBA', (w, h), PANEL + (255,))
    inner = lines.convert('RGBA')
    inner.putalpha(_mask(lines.size, 8))
    card.alpha_composite(label, (pad, pad))
    card.alpha_composite(inner, (pad, pad + label.height + 10))
    e = ease(progress)
    layer = Image.new('RGBA', canvas.size, (0, 0, 0, 0))
    top = y if anchor == 'top' else y - h
    panel(layer, card, (int(center_x - w / 2), int(top + (1 - e) * 16)), r=14)
    if e < 1:
        layer.putalpha(layer.getchannel('A').point(lambda v: int(v * e)))
    canvas.alpha_composite(layer)


def even(v):
    return int(v) + (int(v) % 2)


M, HEAD_Y, HEAD_GAP = 44, 34, 30


def clip_size(w, h, heading, tag=False):
    hd = asset(heading)
    top = HEAD_Y + max(hd.height, asset('brand').height) + HEAD_GAP + (asset('tag-player').height + 12 if tag else 0)
    return even(w + 2 * M), even(top + h + M), top


def clip(content, heading, tag=None, tag_alpha=1.0, glow=0.0):
    """A guide clip: wordmark left, heading right, the page in a panel."""
    W, H, top = clip_size(content.width, content.height, heading, tag is not None)
    cv = background(W, H)
    hd = asset(heading)
    if hd.width + asset('brand').width + 40 > W - 2 * M:
        put(cv, heading, ((W - hd.width) / 2, HEAD_Y))
    else:
        put(cv, 'brand', (M, HEAD_Y + (hd.height - asset('brand').height) / 2))
        put(cv, heading, (W - M - hd.width, HEAD_Y))
    if tag:
        put(cv, tag, (M, top - asset(tag).height - 12), tag_alpha)
    panel(cv, content, (M + (W - 2 * M - content.width) // 2, top), glow=glow)
    return cv.convert('RGB')
