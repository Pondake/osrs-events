# The wordmark without smoothing: the pixel face sits on its grid at size 16.
import os
from PIL import Image, ImageDraw, ImageFont
HERE = os.path.dirname(os.path.abspath(__file__))
font = ImageFont.truetype(os.path.join(HERE, '..', '..', '..', 'public', 'fonts', 'RuneScape-Bold-12.otf'), 16)
mask = Image.new('L', (160, 32), 0)
draw = ImageDraw.Draw(mask)
draw.fontmode = '1'
draw.text((2, 2), 'OSRS Events', fill=255, font=font)
mask = mask.crop(mask.getbbox())
im = Image.new('RGBA', mask.size, (245, 240, 232, 0))
im.putalpha(mask)
im.save(os.path.join(HERE, 'word.png'))
