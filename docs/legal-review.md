# Privacy and terms — what changed, and what still needs a human

**Applied 2026-08-24.** This was a draft; it is now a record. The copy lives in
`App\Support\LegalPages`, PageSeeder plants it on a fresh install, and
`php artisan pages:sync-legal` applies it to a database whose rows already
exist — which is the half that was missing, because `seedPage` uses
`firstOrCreate` and would never have touched an existing page.

```bash
php artisan pages:sync-legal --diff   # what would change
php artisan pages:sync-legal          # apply it
```

It **overwrites** those two page bodies, so anything edited through admin →
Content on `/privacy` or `/terms` is replaced. That is why it is a command
somebody runs on purpose and not something a deploy does quietly.

Still true, and still the point: **this is a legal document and being accurate
is the floor, not the whole bar.** Everything below is what the code does. None
of it is legal advice, and it wants your read before launch.

---

## What went in

**1. Sessions record an IP address and a browser string.**
`sessions.ip_address` and `sessions.user_agent`, written on every request by
Laravel's own session driver. `audit_logs.ip_address` too, on every recorded
admin action. Both are personal data by any reading, and neither was named.

**2. The audit log's retention is now a number, not a shrug.**
Ninety days, from `config/audit.php`. The page already said entries
deliberately outlive a deleted account; saying for how long is what turns that
from a warning into a commitment. **Pinned by a test** — the copy has to state
whatever `AuditLog::retentionDays()` returns, so changing the config without
changing the page fails the suite.

**3. Invite records name two people.**
`board_invites.created_by` and `board_accesses` record who handed a link out
and who accepted it, and both outlive the invite's use.

**4. Notifications.** *(New — 2026-08-23/24, and the reason this got applied
rather than left as a draft.)*
Push registers **a device**: `push_subscriptions` stores the endpoint the
browser hands out, the keys that encrypt a message for that device, and the
user-agent string so somebody can tell their own devices apart. That is a
device identifier per person, which is a category the page did not have at all.

It also introduces a third party we do not choose and cannot avoid: the
browser's push service — Google, Apple or Mozilla. They carry the message and
cannot read it (it is encrypted before it leaves this server), but they learn
that this site sent that device something, and when. Stated plainly rather than
implied.

And the email address gained a purpose it did not have: `EventNotificationService`
mails participants when an event is paused, resumed, cancelled or restored. The
page previously justified that address only as a login credential and a recovery
route.

**5. A team can be tied to a Discord server.**
`teams.guild_id` and `teams.guild_name` — a link between an account's clan and
its Discord server, stored on our side.

**6. Discord announcements.**
Written now rather than when the switch is flipped, and phrased so it is true
either way: a host *can* point an event at a webhook, and when they do, the
event's title and status are posted into that server. A destination the host
chooses, not us.

**7. `/terms` — what a host may do to the people in their event.**
The gap that was named and is now two sections. A host can see participants'
display and OSRS names, approve or reject their claims, remove them, delete the
event along with everyone's progress, and push its status into Discord. Said
from both sides: what you accept by joining, and what you take on by hosting.
The counterweight matters as much — a host is **not** an administrator, and
cannot see your email address or your other events.

---

## Still a judgement call, and still yours

The owner is taking a second pass at this — see the "Legal, round two" section
at the end of `docs/backlog.md` for what they want to look at (licensed policy
sources, writing for a worldwide playerbase rather than an EU-shaped one, and
whether to publish a personal address at all).

- **The deletion address.** `mailto:dev@absolit.nl` is what the page offers as
  the route to erasure. If that is not the address you want handling those, it
  is one line in `LegalPages::privacy()`.
  It is also **the only route there is**: `Admin\UserController::destroy` is
  the sole way an account gets deleted, and there is no button in Settings.
  Build that and the address becomes a fallback rather than the mechanism.
- **"Last updated August 2026"** is accurate today. It moves the next time this
  file does, and nothing enforces that.
- **Retention beyond the audit log.** Sessions expire and push subscriptions are
  marked dead on a 404/410, but neither has a stated maximum age the way audit
  entries now do. If you want a number there, the code needs one first.
- **Whether any of this needs a lawyer.** It is a free hobby project with no
  payments and no advertising, which is the easiest possible case — but "the
  easiest case" is not the same as "no case".
