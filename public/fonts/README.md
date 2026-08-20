# Fonts

## RuneScape-Bold-12.otf

The in-game Old School RuneScape UI face, used for the header wordmark only
(see `resources/css/app.css`'s `--font-osrs-game` and
`Components/AppHeader.vue`). Specified by the logo brief in
`resources/images/logo/README.md`.

- **Source**: [RuneStar/fonts](https://github.com/RuneStar/fonts), release
  `1.103-0`, from `RuneScape-Fonts.zip`
- **Licence**: the repository is published under CC0-1.0

**Worth knowing before reusing this elsewhere**: these are faces extracted
from the game client, so the CC0 dedication is the extractor's claim over
Jagex's assets rather than a grant from Jagex. Same risk profile as the OSRS
Wiki icons this project already pulls in, and the site carries a
"not affiliated with Jagex" disclaimer — but it is a deliberate call, not a
plain public-domain font.

**It is a pixel face designed at 12px.** Render it at 12px or an integer
multiple (24, 36, 48) and keep font smoothing off, or the pixels blur — the
same rule the logo brief sets for the mark itself. Anti-aliased at 17px it
looks like a mistake rather than a style.

Only Bold 12 is committed. The release also ships Plain 11/12, Quill,
Fairy, Barbarian Assault and Surok; pull them from the source release if a
use case ever appears rather than vendoring faces nothing references.
