## What this changes

<!-- One or two sentences. Link the issue if there is one. -->

## Why

<!-- The problem, not the diff. -->

## How to check it

<!-- Steps to see the change working. -->

## Checklist

- [ ] `pnpm test` passes
- [ ] `php artisan test` passes
- [ ] Frontend change: ran `pnpm build` and `pnpm exec vite build --ssr`, and restarted the SSR process
- [ ] Every new user-visible string has a key in `lang/en.json`
- [ ] Everything clickable has `cursor: pointer`, a hover state and a visible focus ring
- [ ] No tokens, keys, invite links, ids or personal data in the diff or the commit messages
- [ ] Targets `develop`
