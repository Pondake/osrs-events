import json, os, bisect
from PIL import Image
HERE = os.path.dirname(os.path.abspath(__file__))
d = json.load(open(os.path.join(HERE, 'frames.json')))
times = [(m['t'] - d['t0']) / d['K'] for m in d['meta']]
files = [m['file'] for m in d['meta']]
b = d['box']; pad = 16 * d['ZOOM']
CROP = (round(b['x'] - pad), round(b['y'] - pad), round(b['x'] + b['width'] + pad), round(b['y'] + b['height'] + pad))
W = 1080
H = round(W * (CROP[3] - CROP[1]) / (CROP[2] - CROP[0]) / 2) * 2
_cache = {}
def at(t):
    i = max(0, bisect.bisect_right(times, t) - 1)
    if i not in _cache:
        _cache.clear()
        _cache[i] = Image.open(os.path.join(HERE, 'frames', files[i])).convert('RGB').crop(CROP).resize((W, H), Image.LANCZOS)
    return _cache[i]
