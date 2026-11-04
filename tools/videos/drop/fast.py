import io, subprocess, sys
from frames import *
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS, FADE = 30, 0.6
MOVES = [(3.03, 3.73), (6.03, 6.73), (8.87, 9.57), (11.87, 12.57)]
SEGS = [(0.3, 1.4)] + [(s - 0.2, e + (1.5 if k == len(MOVES) - 1 else 0.9)) for k, (s, e) in enumerate(MOVES)]
src = [a + i / FPS for a, b in SEGS for i in range(round((b - a) * FPS))]
first = at(src[0]).copy()
with open(os.path.join(HERE, 'fast.mjpg'), 'wb') as f:
    for k, t in enumerate(src):
        im = at(t)
        left = (len(src) - k) / FPS
        if left < FADE:
            im = Image.blend(im, first, 1 - left / FADE)
        buf = io.BytesIO(); im.save(buf, 'JPEG', quality=96); f.write(buf.getvalue())
print('seconds', len(src) / FPS)
for name, crf in [('master.webm', 14), ('drop.webm', 34)]:
    subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + os.path.join(HERE, 'fast.mjpg'),
        '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(crf), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', os.path.join(HERE, name)], check=True)
    print(name, os.path.getsize(os.path.join(HERE, name)))
