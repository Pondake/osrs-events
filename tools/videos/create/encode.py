import io, subprocess, sys
from frames import *
FF = os.environ.get('FFMPEG') or __import__('glob').glob(os.path.expanduser('~/AppData/Local/ms-playwright/ffmpeg-*/ffmpeg-win64.exe'))[-1]
FPS, START, END, FADE = 30, 0.3, float(os.environ.get("END", 21.0)), 0.6

def frames():
    n = round((END - START) * FPS)
    first = at(START).copy()
    for k in range(n):
        t = START + k / FPS
        im = at(t)
        left = END - t
        if left < FADE:
            im = Image.blend(im, first, 1 - left / FADE)
        yield im

SRC = os.path.join(HERE, 'composed.mjpg')

def compose():
    with open(SRC, 'wb') as f:
        for im in frames():
            buf = io.BytesIO(); im.save(buf, 'JPEG', quality=96); f.write(buf.getvalue())

def encode(out, crf):
    subprocess.run([FF, '-y', '-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', str(FPS), '-i', 'file:' + SRC,
        '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', str(crf), '-pix_fmt', 'yuv420p', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '2', out],
        check=True)

compose()
at(START).save(os.path.join(HERE, 'poster.png'))
for name, crf in [('master.webm', 14), ('create.webm', int(sys.argv[1]) if len(sys.argv) > 1 else 34)]:
    encode(os.path.join(HERE, name), crf)
    print(name, os.path.getsize(os.path.join(HERE, name)))
