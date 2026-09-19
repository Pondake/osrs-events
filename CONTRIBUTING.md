# Contributing

This is a solo hobby project, so the honest expectation first: issues and pull
requests are welcome, but a reply can take a while, and a large change that
was not discussed first may not be merged. Open an issue before building
anything big.

What follows is what you need to run, change and verify this app. The
conventions behind the code — Eloquent and SSR rules, the notification
catalogue, the settings contract, naming — live in
[`CLAUDE.md`](CLAUDE.md).

## Setup

```bash
composer setup
```

See the [README](README.md) for the environment variables that matter and for
why `php artisan serve` is the wrong server for any page with a live stream.

## Package managers

**pnpm** for JavaScript, **composer** for PHP. Never `npm`, `npx` or `yarn` —
the lockfile is pnpm's and the others will fight it.

## Tests

Both suites must pass:

```bash
pnpm test           # vitest, over tests/js
php artisan test    # phpunit, over tests/Feature
```

## After a frontend change

Both bundles need rebuilding, and the SSR process restarting — it loads the
bundle once at startup, not per request:

```bash
pnpm build                    # client bundle -> public/build
pnpm exec vite build --ssr    # SSR bundle -> bootstrap/ssr/ssr.js
```

`pnpm build:ssr` does both in one go.

## Translations

Every user-visible string goes through `lang/en.json`, a flat file with
literal dotted keys (`"boards.title": "Boards"`). No hardcoded English in a
template or a script, and the key goes in the same commit as the component
that uses it.

`$t()` is template-only; from `<script setup>` you need `trans()`. The full
rules, including the placeholder syntax, are in [`CLAUDE.md`](CLAUDE.md).

## Branches

- `develop` — the default branch. Branch from it, and open pull requests against it.
- `master` — production. It only ever receives merges from `develop`.

## Commits

Short subject lines, imperative mood, one concern per commit. Keep code
comments sparse — say why something is unusual, not what the next line does.

Nothing sensitive in a commit, a file or a commit message: no tokens, keys,
invite links, ids or personal data.

## Backlog

The backlog is not in this repository, and please do not add one here —
those paths are gitignored, so a file there would silently get lost. Open an
issue instead.
