"""Builds public/images/osrs/rune-field.webp, the rune sheet behind the home page.

Every rune from the OSRS Wiki, reduced to one flat shape: the stone with its
symbol cut out. The sheet is an alpha mask, so AppBackground.vue decides the
colour and all runes share it. Rerun when the game adds a rune:

    pip install pillow numpy scipy
    python tools/rune-field/build.py

CELL and COLS must match the `runes` shape in AppBackground.vue.
"""
import io
import random
import urllib.request
from pathlib import Path

import numpy as np
from PIL import Image
from scipy import ndimage as ndi

RUNES = (
    'Air Water Earth Fire Sunfire Mist Dust Mud Smoke Steam Lava Aether Mind Body '
    'Cosmic Chaos Nature Law Death Astral Blood Soul Wrath Cabbage'
).split()

CELL = 48
COLS = 8
RUNE_SIZE = 37
MAX_TILT = 9
DENSITY = 2
OUTPUT = Path(__file__).resolve().parents[2] / 'public/images/osrs/rune-field.webp'

WHITE_SYMBOLS = {'Air', 'Mist', 'Steam', 'Death', 'Aether', 'Astral'}


def fetch(name):
    url = f'https://oldschool.runescape.wiki/images/thumb/{name}_rune_detail.png/200px-{name}_rune_detail.png'
    request = urllib.request.Request(url, headers={'User-Agent': 'osrs-events-asset-build/1.0'})
    with urllib.request.urlopen(request) as response:
        return Image.open(io.BytesIO(response.read())).convert('RGBA').resize((400, 400), Image.BICUBIC)


def symbol(name, sat, val, stone):
    edge = ndi.distance_transform_edt(stone)
    inner = edge > 26
    plain = np.median(val[inner & (sat < 0.12)])
    # The stone's bevel is as bright and as dark as a symbol, so stay off the rim.
    dark = (val < plain - 0.17) & (edge > 44)
    light = (val > plain + 0.13) & (edge > 44)
    colour = sat > 0.17

    if name == 'Chaos':
        return inner & ((sat > 0.45) | dark)
    if name == 'Smoke':
        return ndi.binary_fill_holes(inner & (edge > 44) & (val < plain - 0.07))
    if name == 'Cabbage':
        return inner & (dark | ((sat > 0.45) & (val < 0.62)))
    if name == 'Wrath':
        return inner & dark & (sat < 0.35)
    if name in WHITE_SYMBOLS:
        return inner & (colour | dark | light)
    return inner & (colour | dark)


def stencil(name):
    pixels = np.asarray(fetch(name), dtype=float) / 255
    rgb, alpha = pixels[..., :3], pixels[..., 3]
    stone = ndi.binary_fill_holes(alpha > 0.5)
    high, low = rgb.max(-1), rgb.min(-1)
    sat = (high - low) / np.maximum(high, 1e-3)

    cut = symbol(name, sat, high, stone)
    cut = ndi.binary_closing(ndi.binary_opening(cut, iterations=2), iterations=3)

    labels, count = ndi.label(cut)
    edge = ndi.distance_transform_edt(stone)
    keep = [
        i for i in range(1, count + 1)
        if (labels == i).sum() >= 220 and edge[labels == i].max() >= 52
    ]
    cut = np.isin(labels, keep)

    return Image.fromarray(((stone & ~cut) * 255).astype('uint8'))


def layout():
    """Most-spread placement: the same rune never lands near itself on the torus."""
    rng = random.Random(7)
    slots = [(col, row) for row in range(COLS) for col in range(COLS)]
    rng.shuffle(slots)
    placed = {name: [] for name in RUNES}

    def gap(a, b):
        dx = min(abs(a[0] - b[0]), COLS - abs(a[0] - b[0]))
        dy = min(abs(a[1] - b[1]), COLS - abs(a[1] - b[1]))
        return dx + dy

    grid = {}
    for slot in slots:
        fewest = min(len(spots) for spots in placed.values())
        pool = [n for n in RUNES if len(placed[n]) == fewest]
        name = max(pool, key=lambda n: min((gap(slot, s) for s in placed[n]), default=99))
        placed[name].append(slot)
        grid[slot] = name
    return grid, rng


def main():
    stencils = {name: stencil(name) for name in RUNES}
    grid, rng = layout()

    side = CELL * COLS * DENSITY
    size = RUNE_SIZE * DENSITY
    sheet = np.zeros((side, side), dtype=float)

    for (col, row), name in grid.items():
        # Rotated at full size, so the tilt costs no sharpness when it shrinks.
        tilted = stencils[name].rotate(rng.uniform(-MAX_TILT, MAX_TILT), Image.BICUBIC)
        mark = np.asarray(tilted.resize((size, size), Image.LANCZOS), dtype=float) / 255
        x = (col * CELL + (CELL // 2 if row % 2 else 0) + (CELL - RUNE_SIZE) // 2) * DENSITY
        y = (row * CELL + (CELL - RUNE_SIZE) // 2) * DENSITY
        for dy in range(size):
            for dx in range(size):
                if mark[dy, dx]:
                    sheet[(y + dy) % side, (x + dx) % side] = mark[dy, dx]

    # Sixteen levels of edge softness look the same here and cut the file by 60%.
    alpha = (np.round(sheet * 15) * 17).astype('uint8')
    rgba = np.dstack([np.full_like(alpha, 255)] * 3 + [alpha])
    Image.fromarray(rgba).save(OUTPUT, lossless=True, quality=100, method=6, exact=False)
    print(f'{OUTPUT.name}: {OUTPUT.stat().st_size / 1024:.1f} KB, {len(RUNES)} runes, {COLS ** 2} placements')


if __name__ == '__main__':
    main()
