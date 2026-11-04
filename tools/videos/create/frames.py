import json, os, bisect
from PIL import Image
HERE = os.path.dirname(os.path.abspath(__file__))
d = json.load(open(os.path.join(HERE, 'frames.json')))
TS = [(x['t'] - d['t0']) / d['K'] for x in d['meta']]
FILES = [os.path.join(HERE, 'frames', x['file']) for x in d['meta']]
W, H = 1536, 1350
_cache = {}
def at(t):
    i = max(0, bisect.bisect_right(TS, t) - 1)
    if i not in _cache:
        _cache.clear()
        _cache[i] = Image.open(FILES[i]).convert('RGB').resize((W, H), Image.LANCZOS)
    return _cache[i]
