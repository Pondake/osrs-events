import json, os, bisect
from PIL import Image, ImageDraw, ImageFont
HERE = os.path.dirname(os.path.abspath(__file__))
d = json.load(open(os.path.join(HERE, 'frames.json')))
SIDES = {}
for side, m in d['meta'].items():
    SIDES[side] = ([(x['t'] - d['t0']) / d['K'] for x in m], [os.path.join(HERE, 'frames-' + side, x['file']) for x in m])
W, H, TOP = 1920, 1312, 64
HALF = 954
FONT = ImageFont.truetype('C:/Windows/Fonts/segoeuib.ttf', 26)
_cache = {}
def side_at(side, t):
    ts, files = SIDES[side]
    i = max(0, bisect.bisect_right(ts, t) - 1)
    if (side, i) not in _cache:
        im = Image.open(files[i]).convert('RGB')
        _cache[(side, i)] = im.resize((HALF, round(HALF * im.height / im.width)), Image.LANCZOS)
    return _cache[(side, i)]
def label(draw, cx, text):
    w = draw.textlength(text, font=FONT) + 36
    draw.rounded_rectangle((cx - w / 2, 10, cx + w / 2, 54), radius=22, fill=(245, 166, 35))
    draw.text((cx, 32), text, font=FONT, fill=(28, 25, 23), anchor='mm')
def at(t):
    canvas = Image.new('RGB', (W, H), (12, 11, 10))
    canvas.paste(side_at('player', t), (0, TOP))
    canvas.paste(side_at('host', t), (W - HALF, TOP))
    dr = ImageDraw.Draw(canvas)
    label(dr, HALF / 2, 'Player')
    label(dr, W - HALF / 2, 'Host')
    return canvas
