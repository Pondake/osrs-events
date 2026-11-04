import json, os, bisect
from PIL import Image
HERE = os.path.dirname(os.path.abspath(__file__))
d = json.load(open(os.path.join(HERE, 'frames.json')))
times = [(m['t'] - d['t0']) / d['K'] for m in d['meta']]
files = [m['file'] for m in d['meta']]
b = d['box']; pad = 20 * d['ZOOM']
CROP = (round(b['x'] - pad), round(b['y'] - pad), round(b['x'] + b['width'] + pad), round(b['y'] + b['height'] + pad))
_cache = {}
def at(t, size=1080):
    i = max(0, bisect.bisect_right(times, t) - 1)
    key = (i, size)
    if key not in _cache:
        _cache.clear()
        _cache[key] = Image.open(os.path.join(HERE, 'frames', files[i])).convert('RGB').crop(CROP).resize((size, size), Image.LANCZOS)
    return _cache[key]
