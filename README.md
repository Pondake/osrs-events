# OSRS Events — coming soon

One static page, and nothing else. This branch exists to be deployed on its
own while the app itself is still being built; it shares no history with
`experiment/laravel-stack` (it is an orphan branch), so nothing here can drag
application code onto a public host by accident.

## Deploying on Ploi

- Point the site at this repository and set the branch to `coming-soon`.
- **Set the web root to `/` — not `/public`.** This is a static page, not a
  Laravel app, and there is no `public` directory here.
- No build step, no deploy script, no `composer install`. Clear the deploy
  script entirely; the default one runs Laravel commands that will fail
  because there is no application to run them against.

## Editing it

The page is a single self-contained file: the CSS is inline, the logo is
inline SVG, and the favicon is a data URI. It pulls exactly one external
resource, the Google Fonts stylesheet for Cinzel — the same face the app
uses for headings.

The source of truth lives at `docs/coming-soon/index.html` on
`experiment/laravel-stack`. Edit it there and copy it across, so the two
cannot drift.

## Before it goes live

- `og:image` points at `/og-image.png`, which does not exist on this branch.
  Either add the file or make the meta tag an absolute URL to wherever it
  ends up.
- There is no Discord invite on the page. That is deliberate — a dead invite
  on a launch page is worse than none — so drop the real one in when there is
  one to drop.
