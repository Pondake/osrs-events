"""Builds public/images/osrs/fields/*.webp, the sheets behind the landing pages.

Each sheet is a set of things from the game, reduced to one flat shape. It is
an alpha mask, so AppBackground.vue decides the colour and everything on it
shares that. Rerun when the game adds a rune or a skill:

    pip install pillow numpy scipy
    python tools/fields/build.py [sheet ...]

CELL and COLS must match the sheet shape in AppBackground.vue.
"""
import io
import random
import sys
import urllib.parse
import urllib.request
from pathlib import Path

import numpy as np
from PIL import Image
from scipy import ndimage as ndi

CELL = 64
COLS = 8
DENSITY = 2
OUTPUT = Path(__file__).resolve().parents[2] / 'public/images/osrs/fields'

WHITE_SYMBOLS = {'Air', 'Mist', 'Steam', 'Death', 'Aether', 'Astral'}


def fetch(title):
    url = 'https://oldschool.runescape.wiki/images/' + urllib.parse.quote(title.replace(' ', '_'))
    request = urllib.request.Request(url, headers={'User-Agent': 'osrs-events-asset-build/1.0'})
    with urllib.request.urlopen(request) as response:
        return Image.open(io.BytesIO(response.read())).convert('RGBA')


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


def rune(name):
    """The stone with its symbol cut out: every rune has the same outline."""
    image = fetch(f'{name} rune detail.png').resize((400, 400), Image.LANCZOS)
    pixels = np.asarray(image, dtype=float) / 255
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


def silhouette(title, depth=0.16):
    """A thing recognisable from its outline, plus creases deeper than depth."""
    image = fetch(title)
    longest = max(image.size)
    side = (round(image.width * 800 / longest), round(image.height * 800 / longest))
    pixels = np.asarray(image.resize(side, Image.LANCZOS), dtype=float) / 255
    alpha = ndi.gaussian_filter(pixels[..., 3], 9 if longest < 100 else 2) > 0.5
    light = pixels[..., :3].max(-1)

    shade = np.where(alpha, light, np.median(light[alpha]))
    crease = (shade - ndi.gaussian_filter(shade, 24)) < -depth
    crease &= ndi.distance_transform_edt(alpha) > 22
    crease = ndi.binary_opening(crease, iterations=4)

    shape = Image.fromarray(((alpha & ~crease) * 255).astype('uint8'))
    shape = shape.crop(shape.getbbox())
    scale = 400 / max(shape.size)
    shape = shape.resize((max(1, round(shape.width * scale)), max(1, round(shape.height * scale))), Image.LANCZOS)
    square = Image.new('L', (400, 400))
    square.paste(shape, ((400 - shape.width) // 2, (400 - shape.height) // 2))
    return square


def outline(title):
    """Skill icons carry a black rim that a crease would trace all the way round."""
    return silhouette(title, depth=1)


def faint(title):
    """Paper is flat: without its fainter creases a page is a blank blob."""
    return silhouette(title, depth=0.08)


def details(names):
    return [f'{name} detail.png' for name in names.split(', ')]


SHEETS = {
    'runes': {
        'things': (
            'Air Water Earth Fire Sunfire Mist Dust Mud Smoke Steam Lava Aether Mind Body '
            'Cosmic Chaos Nature Law Death Astral Blood Soul Wrath Cabbage'
        ).split(),
        'shape': rune, 'size': 37, 'turn': 30,
    },
    'skills': {
        'things': [
            f'{name} icon (detail).png' for name in (
                'Attack Strength Defence Ranged Prayer Magic Runecraft Construction Hitpoints '
                'Agility Herblore Thieving Crafting Fletching Slayer Hunter Mining Smithing '
                'Fishing Cooking Firemaking Woodcutting Farming'
            ).split()
        ],
        'shape': outline, 'size': 38, 'turn': 14,
    },
    'bones': {
        'things': details(
            'Bones, Big bones, Dragon bones, Wyvern bones, Superior dragon bones, Lava dragon bones, '
            'Bat bones, Dagannoth bones, Hydra bones, Long bone, Curved bone, Ensouled dragon head, '
            'Ensouled demon head, Ensouled giant head, Ensouled dagannoth head, Skeletal visage'
        ),
        'shape': silhouette, 'size': 42, 'turn': 180,
    },
    'keys': {
        'things': details(
            "Brass key, Crystal key, Dusty key, Shiny key, Loop half of key, Tooth half of key, "
            "Mossy key, Giant key, Sinister key, Muddy key, Dark key, Larran's key, Brimstone key, "
            "Enhanced crystal key, Weapon store key"
        ),
        'shape': silhouette, 'size': 42, 'turn': 180,
    },
    'scrolls': {
        'things': details(
            'Clue scroll (beginner), Clue scroll (easy), Clue scroll (master), Ancient page, '
            'Journal page, Bank note, Death note, Arcane prayer scroll, Dexterous prayer scroll, '
            'Torn prayer scroll'
        ),
        'shape': faint, 'size': 40, 'turn': 30,
    },
    'treasure': {
        'things': details(
            "Coins, Red partyhat, Santa hat, Christmas cracker, Gold ring, Ring of wealth, "
            "Ring of coins, Amulet of glory, Gold necklace, Pharaoh's sceptre, Golden tench, "
            "Gem bag, Casket, Diamond, Dragonstone, Zenyte, Platinum token"
        ),
        'shape': faint, 'size': 40, 'turn': 30,
    },
}


def layout(things):
    """Most-spread placement: the same thing never lands near itself on the torus."""
    rng = random.Random(7)
    slots = [(col, row) for row in range(COLS) for col in range(COLS)]
    rng.shuffle(slots)
    placed = {thing: [] for thing in things}

    def gap(a, b):
        dx = min(abs(a[0] - b[0]), COLS - abs(a[0] - b[0]))
        dy = min(abs(a[1] - b[1]), COLS - abs(a[1] - b[1]))
        return dx + dy

    grid = {}
    for slot in slots:
        fewest = min(len(spots) for spots in placed.values())
        pool = [t for t in things if len(placed[t]) == fewest]
        thing = max(pool, key=lambda t: min((gap(slot, s) for s in placed[t]), default=99))
        placed[thing].append(slot)
        grid[slot] = thing
    return grid, rng


def build(name, things, shape, size, turn):
    shapes = {thing: shape(thing) for thing in things}
    grid, rng = layout(things)

    side = CELL * COLS * DENSITY
    # Half as much frame again, so corners survive the turn.
    frame = size * DENSITY * 3 // 2
    inset = (CELL * DENSITY - frame) // 2
    sheet = np.zeros((side, side), dtype=float)

    for (col, row), thing in grid.items():
        framed = Image.new('L', (600, 600))
        framed.paste(shapes[thing], (100, 100))
        turned = framed.rotate(rng.uniform(-turn, turn), Image.BICUBIC)
        mark = np.asarray(turned.resize((frame, frame), Image.LANCZOS), dtype=float) / 255
        x = (col * CELL + (CELL // 2 if row % 2 else 0)) * DENSITY + inset
        y = row * CELL * DENSITY + inset
        for dy in range(frame):
            for dx in range(frame):
                if mark[dy, dx]:
                    sheet[(y + dy) % side, (x + dx) % side] = mark[dy, dx]

    # Sixteen levels of edge softness look the same here and cut the file by 60%.
    alpha = (np.round(sheet * 15) * 17).astype('uint8')
    rgba = np.dstack([np.full_like(alpha, 255)] * 3 + [alpha])
    path = OUTPUT / f'{name}.webp'
    Image.fromarray(rgba).save(path, lossless=True, quality=100, method=6, exact=False)
    print(f'{path.name}: {path.stat().st_size / 1024:.1f} KB, {len(things)} things')


if __name__ == '__main__':
    OUTPUT.mkdir(exist_ok=True)
    for name in sys.argv[1:] or SHEETS:
        build(name, **SHEETS[name])
