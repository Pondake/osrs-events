# osrs-events — logo 5a

Pixel-mark op een 16×16 grid. Alle PNG's zijn met integer-scaling gerenderd, dus elke pixel blijft scherp.

## Bestanden

| Bestand | Gebruik |
| --- | --- |
| `osrs-events-logo-color.svg` | primaire mark, werkt op licht én donker |
| `osrs-events-logo-mono-dark.svg` | één kleur (#1c1919) op lichte achtergrond |
| `osrs-events-logo-mono-light.svg` | één kleur (#e6d9b8) op donkere achtergrond |
| `osrs-events-logo-mono-gold.svg` | één kleur (#e8b64c), o.a. Discord |
| `favicon-16/32/48/64/128/256/512.png` | favicon + PWA-iconen |
| `apple-touch-icon-180.png` | iOS, met dichte #1c1919 achtergrond |
| `mono-*-512.png` | monochroom raster, voor print/stickers |

## Palet

| Rol | Hex |
| --- | --- |
| Achtergrond | `#1c1919` |
| Accent (ember, linker mok) | `#e0762f` |
| Accent (goud, rechter mok) | `#d4a33e` |
| Highlight / schuim | `#ffcf5c` |
| Parchment (tekst op donker) | `#e6d9b8` |

## HTML

```html
<link rel="icon" type="image/svg+xml" href="/osrs-events-logo-color.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="apple-touch-icon" href="/apple-touch-icon-180.png">
```

## Font

Het wordmark gebruikt het in-game font (RuneScape Bold 12). Voor productie: self-host de TTF/OTF uit
[RuneStar/fonts](https://github.com/RuneStar/fonts) (CC0) in plaats van een webfont-CDN.

Chat-stijl tekst: `color: #ffff00; text-shadow: 2px 2px 0 #000;`

## Regels

- Minimale ruimte rondom = 1 grid-pixel (1/16 van de markbreedte).
- Nooit roteren of niet-integer schalen — dat maakt de pixels vaag.
- Op achtergronden tussen `#555` en `#aaa`: gebruik de kleurversie, niet mono.
