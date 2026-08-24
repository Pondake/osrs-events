---
name: multi-user-walkthrough
description: Walk the app as every kind of user, at every screen size, and write down what each one runs into. Use when asked to test the app "as different users", "on mobile/tablet/desktop", to check permissions end to end, before a release, or after a change that touches who-can-do-what or the shape of a page.
---

# Walking the app as everybody

Six seats, four widths, one pass. Every UX bug found in this project so far
came out of this: the join that said "you joined" and did nothing, the
leaderboard that said "no players yet" about five people, the 390px overflow
that only existed at tablet width, the invite tab where every button answered
403. None of them were errors. All of them told somebody something untrue.

## What is already decided

Do not re-litigate these at the start of every pass.

**Six seats, in this order.** Each one is the previous one plus something.

| Seat | Is | Should reach |
|---|---|---|
| `guest` | not signed in | public pages, guides, the events list |
| `player` | signed in, no permissions | events, joining, teams, own settings |
| `creator` | `canCreateBoards` | the above, plus creating events and `/admin/blueprints` |
| `cohost` | author of one event, not owner | that event's editing, pausing, invite links |
| `owner` | owner of an event | the above, plus deleting it |
| `admin` | ADMIN role | everything, via `/admin` — and NOT via the public routes |

**Two more when the change touches onboarding or notifications:**
`newcomer` (no OSRS name, wizard still open) and `no-email` (a Discord-only
account, which cannot be mailed).

**Four widths: 1280, 1024, 768, 375.** Not three. The tablet band between
`sm` and `lg` is where this project's layout bugs have actually been, and it
is the one people skip. Set the viewport explicitly and *assert the width you
got* — a browser pane reporting 483px is not a desktop, and a sweep that
believes it is proves nothing.

**One browser session, re-roled between seats.** Not six logins: that means
six passwords typed into a form on every pass, and the app's own rule is that
an admin hands out roles, so a second account cannot elevate itself into the
seat you need anyway. `dev:persona` reshapes the seat instead.

## Set up

```bash
php artisan dev:fixtures
```

Seeds the events a walkthrough needs and demo data never produces: a title
long enough to wrap four times, an event that ended, one that has not
started, one on hold, a drop race, an invite-only event and a team event.
Idempotent — run it before every pass.

```bash
php artisan dev:persona show
php artisan dev:persona player
php artisan dev:persona cohost --event="Winter"
php artisan dev:persona restore
```

`restore` puts back exactly what was there before the first switch, from a
snapshot taken at that moment. **It is not optional and it is not the last
thing you remember to do — it is a step.** Leaving the seat demoted turns
every later session into a confusing bug report.

Assumptions this relies on, worth checking once if the environment is new:

- `APP_ENV=local`. Both commands refuse to run anywhere else.
- An account exists to be the seat (`--as=admin` by default), and it is the
  one the browser is signed in as.
- The pre-launch site lock. With it on, a demoted seat gets the lock screen
  instead of the app and every result is a false negative — check
  `Setting::get('site_lock_enabled')` before blaming the code, and put it
  back afterwards.
- On Windows, `php artisan serve` serves one SSE stream at a time, so a
  request made while an event page is open will queue behind it for ~45
  seconds. Navigate away from event pages before driving anything else, or
  use Herd.

## The pass

For each seat, in the order above:

1. `php artisan dev:persona <seat>`
2. Reload the browser (the seat's props are server-rendered).
3. Walk: `/events` → an event of each type → try the thing this seat should
   be able to do → try the thing the next seat up can do and this one cannot.
4. Record what happened, not what should have happened.

What to look for per seat, beyond status codes:

- **guest** — does anything private leak into a public page? Does every
  protected route redirect to login rather than 403?
- **player** — is anything visible that they cannot use? A button that 403s
  is worse than no button.
- **creator** — is "create" offered everywhere it works, and nowhere it
  does not?
- **cohost** — the sharpest seat. Everything a host does should work;
  everything that destroys other people's work should not.
- **owner** — the destructive actions, and whether they are behind enough
  friction to be deliberate.
- **admin** — on the PUBLIC side an admin is an ordinary user. If they can
  edit somebody else's event from its own page, that is a bug. If they can
  read a private event and the page does not say so, that is also a bug.
- **newcomer** — does the wizard ask for the same thing twice? Is there a way
  out of every gate?

## Then the widths

For each width, on each event type plus the hub, my-events, the admin list
and teams:

```js
(() => {
  const d = document.documentElement;
  const inner = [...document.querySelectorAll('*')]
    .filter(e => e.scrollWidth > e.clientWidth + 4 && e.clientWidth > 150)
    .map(e => e.className.toString().slice(0, 40)).slice(0, 3);
  const small = [...document.querySelectorAll('button,a')]
    .filter(b => { const r = b.getBoundingClientRect(); return r.height > 0 && r.height < 44; }).length;
  return JSON.stringify({ w: d.clientWidth, overflow: d.scrollWidth - d.clientWidth, inner, under44: small });
})()
```

- `overflow` must be 0 at every width. Anything else means something cannot
  shrink — usually a `truncate` (which is `white-space: nowrap`) inside a
  flex chain where the parents still default to `min-width: auto`. Clamping
  the innermost element does nothing; the whole chain needs `min-w-0`.
- `under44` must be 0 at 375. Above `sm` it is expected and fine.
- `inner` is not automatically a bug: a tab bar or a `truncate` reporting a
  wider `scrollWidth` is doing its job. A page-level overflow never is.

And once per pass, in **both** themes:

```js
(() => {
  const cv = document.createElement('canvas'); cv.width = cv.height = 1;
  const ctx = cv.getContext('2d', { willReadFrequently: true });
  const dark = document.documentElement.classList.contains('dark');
  const rgb = c => { ctx.clearRect(0,0,1,1); ctx.fillStyle = dark ? '#000' : '#fff'; ctx.fillRect(0,0,1,1);
    ctx.fillStyle = c; ctx.fillRect(0,0,1,1); const d = ctx.getImageData(0,0,1,1).data; return [d[0],d[1],d[2]]; };
  const lum = ([r,g,b]) => [r,g,b].map(v => { v/=255; return v <= 0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055, 2.4); })
    .reduce((a,v,i) => a + [0.2126,0.7152,0.0722][i]*v, 0);
  const ratio = (a,b) => { const l1 = lum(rgb(a)), l2 = lum(rgb(b));
    return +(((Math.max(l1,l2)+0.05)/(Math.min(l1,l2)+0.05)).toFixed(2)); };
  const bg = getComputedStyle(document.body).backgroundColor; const out = { theme: dark ? 'dark' : 'light' };
  for (const [k, sel] of [['muted','.text-muted'],['dimmed','.text-dimmed'],['warning','.text-warning'],
    ['success','.text-success'],['error','.text-error'],['primary','.text-primary']]) {
    const e = document.querySelector(sel); if (e) out[k] = ratio(getComputedStyle(e).color, bg);
  }
  return JSON.stringify(out);
})()
```

Everything must clear 4.5. Measure, never eyeball — the status colours passed
by eye in dark mode and scored 1.91 in light.

## Finishing

1. `php artisan dev:persona restore`, then confirm with `show`.
2. Put the site lock back if the pass turned it off; leave no event paused or
   deleted that the pass created.
3. Write the findings into `docs/backlog.md` as a dated section — each one
   with the measurement that found it, so it can be re-checked rather than
   re-argued.
4. **Turn the durable ones into tests.** A permission that was wrong once
   belongs in `tests/Feature/PermissionMatrixTest.php`, not in a paragraph.
   A walkthrough that is not written down as a test has to be walked again.
5. Run `php artisan test` and `pnpm test` before calling it done.

## Two traps this has already fallen into

- `actingAs()` sets the guard for the rest of a test. A matrix whose guest row
  runs after an authenticated one is testing whoever ran last. Call
  `$this->app['auth']->forgetGuards()` between personas.
- The OSRS-name gate deliberately stands down while the first-run wizard is
  open, because the wizard asks for the same field. A test that expects the
  redirect without completing onboarding is asserting the wrong thing.
