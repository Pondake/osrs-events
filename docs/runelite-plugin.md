# 🎣 RuneLite plugin — feasibility notes

> Field notes from building and shipping a working RuneLite → external-service
> integration on **2026-08-19**. Everything marked **[proven]** was observed
> running against a live account, not inferred from documentation.
>
> Context: [ROADMAP.md](./ROADMAP.md) Phase 4 already specifies
> `POST /webhook/runelite`, a per-user token, and auto-completing a tile from a
> matching event. [PROGRESS.md](./PROGRESS.md) already has
> `CompletionSource: MANUAL | RUNELITE`. **The plan was right — this document is
> the evidence that it is buildable, plus the detail needed to build it.**

---

## Why this matters commercially

ROADMAP Phase 5's search note says it plainly: the bingo niche is crowded and
several competitors already ship **automated drop tracking**. Manual screenshot
verification is the single biggest reason a clan picks a competitor. A plugin
that closes a tile the instant the drop happens is the differentiator, not
polish.

The good news is that the hard part is not hard.

---

## What was proven

A RuneLite plugin can push structured, near-real-time game data to an external
HTTP service, with per-user auth, and have it land in under a second.

| Step | Result |
|---|---|
| Plugin reads live game state | **[proven]** via `client.getSkillExperience(skill)` and similar |
| Sends JSON over HTTP with a token | **[proven]** |
| Server accepts and applies it | **[proven]** |
| Latency, event → server | **[proven]** sub-second, repeatedly |
| Only changed values transmitted | **[proven]** idle skills generate zero traffic |

Concretely, gaining fishing XP produced this within about a second of the catch:

```json
{"entities":[{"xp":13041253,"entity_id":"...skill_fishing"}]}
```

Repeated catches tracked cleanly at +80 each. Nothing was polled — the client
pushed on change.

---

## Architecture that works

The integration studied uses a shape that maps almost exactly onto Phase 4:

```
RuneLite client
  └─ plugin subscribes to game events (GameTick, StatChanged, …)
       └─ change detection per field
            └─ coalesce by target id (last write wins in a tick window)
                 └─ optional global tick throttle
                      └─ HTTP POST + long-lived token ──▶ external service
```

Four details worth copying wholesale:

1. **Change detection per field.** Keep `previous*` maps and emit only what
   actually moved. This is what stops a plugin melting a webhook endpoint.
2. **Coalesce by id before sending.** Keep a `Map<targetId, payload>` and
   overwrite — a burst of changes to the same thing becomes one request.
3. **A user-configurable tick throttle.** Lets a player trade latency for
   traffic without a code change.
4. **Config toggles per data type, defaulting to off.** Users opt in to what
   gets sent. It is also a privacy stance you can point at.

For osrs-events the external service is a Laravel route, and the token is the
per-user token Phase 4 already calls for.

---

## Event surface

### Proven available in the integration studied

| Event | Payload | Use for a board |
|---|---|---|
| collection log entry | `item_name` | first-time rare drops — ideal bingo tiles |
| combat task | `task_name`, `tier` | combat achievement tiles |
| achievement diary | `task_name`, `tier` | diary tiles |
| varbit change | `varbit_id`, `old`, `new` | **any** watchable game state |
| idle | — | session boundaries |
| per-skill XP | `xp` per skill, on change | progression tiles, XP races |
| live stats | health, prayer, run energy, spec | not board-relevant, but proves latency |

**The varbit hook is the sleeper.** It turns "watch an arbitrary game variable"
into configuration rather than code — minigame scores, quest stages, diary
progress. It needs the varbit id, which is game knowledge rather than something
derivable from the codebase.

### Available from RuneLite's own API for a purpose-built plugin — not yet tested

A plugin you write is not limited to the above. RuneLite exposes
`LootReceived` / `NpcLootReceived` (the loot tracker's source),
`ItemContainerChanged`, `ChatMessage`, `StatChanged` and more. These are the
right primitives for **quantity** tiles.

> ⚠ **Collection-log events only fire on a _new_ log entry** — the first time an
> item is received. A tile reading "5× Zulrah scale" will **not** work from
> collection-log events; that needs loot events. Do not design tiles around the
> collection log without checking this first.

---

## Development workflow — proven

No special access is required. The plugin template is a normal Gradle project.

```bash
git clone <plugin-repo>
cd <plugin>
./gradlew build          # compiles and runs tests
./gradlew run            # launches a real RuneLite client in developer mode
```

`./gradlew run` is the intended dev loop — it starts an actual client with your
plugin loaded, using your existing `~/.runelite` profile, so settings and other
plugins carry over. The first run pulls the RuneLite client into the Gradle
cache: a few hundred MB, a few minutes, once.

Do **not** try to side-load a JAR. The template's `shadowJar` task bundles the
entire client to support `run`, and is not a distributable plugin artifact.

### Logging into a Jagex Account from a dev client — proven

This is the one genuine snag, and it has an official answer. A Gradle-launched
client never receives the session tokens the Jagex Launcher passes to the client
it spawns, so login fails by default.

Per [the RuneLite wiki](https://github.com/runelite/runelite/wiki/Using-Jagex-Accounts):

1. Launcher version 2.6.3 or newer
2. Start menu → **RuneLite (configure)**
3. Add `--insecure-write-credentials` to **Client arguments**, save
4. Launch once through the Jagex Launcher — this writes
   `~/.runelite/credentials.properties`
5. `./gradlew run` now picks those credentials up automatically

> 🔒 **Treat `credentials.properties` as a password.** It permits login
> *bypassing your password*. Delete it and remove the launcher argument when a
> dev session ends. "End sessions" on runescape.com invalidates it if it ever
> leaks. Never commit it and never let it near a repo.

Practical notes: one session per account, so log out of your normal client
first. Using a spare account for development sidesteps that entirely.

---

## Mapping to tiles

The pieces line up with the existing schema with almost no new concepts:

| Board need | Source | Notes |
|---|---|---|
| Rare drop tile | collection log event | first-time only — see warning above |
| Quantity tile ("5× X") | loot events | needs a purpose-built plugin |
| Skill level / XP tile | live per-skill XP | **[proven]** sub-second |
| Combat achievement tile | combat task event | carries tier |
| Diary tile | achievement diary event | carries tier |
| Minigame score tile | varbit watch | needs the varbit id |
| Boss KC tile | hiscores, or a kill-counter varbit | ⚠ see below |

> ⚠ **Hiscores are useless for live tracking.** Measured: a skill's hiscore XP
> changed **4 times in 2 days** and lagged by more than 24 hours while the
> player was actively training. Anything that must feel live has to come from
> the client, not the hiscores API. Use hiscores only to seed a player's
> starting state.

### Server side

Phase 4's design already matches. Two things worth deciding early:

- **Idempotency.** The client can retry, and a tile must not complete twice. A
  client-generated event id, unique per `(user, event)`, is the simplest guard.
- **Trust boundary.** Anything the plugin sends is user-controlled and trivially
  forgeable — it is a local HTTP client holding a token. For casual clan events
  that is fine. For anything competitive, treat plugin completions as *claims*
  and keep the manual/screenshot path for disputes. `CompletionSource` already
  models this distinction; consider surfacing it in the UI so a board owner can
  see which tiles were auto-claimed.

---

## Distribution

**You do not need to be "a RuneLite developer" to publish.** The Plugin Hub is
open: submit a PR to `runelite/plugin-hub` containing a manifest that points at
your plugin repo and a commit hash. Review is a maintainer reading the code for
obvious abuse. The plugin studied here is a hobbyist project published exactly
that way.

A realistic path:

1. Build against a dev client in a private repo
2. Point it at a staging osrs-events instance
3. Submit to the Plugin Hub once stable
4. Users install from inside RuneLite and paste their board token

---

## Gotchas collected the hard way

- **A field that exists but never changes usually means "not enabled", not "not
  supported".** A stat sat frozen for 24 hours purely because its toggle was off
  in the plugin config. Check configuration before concluding a limitation.
- **Strict server-side validation silently drops unknown fields.** A receiving
  endpoint rejected an added field with a 400 *before* reaching the handler — the
  sending side looked correct and did nothing at all. If you add a field to the
  payload, add it to the request validation in the same commit.
- **Re-adding an integration can rename every identifier it owns.** 148
  identifiers changed prefix in a single step, silently breaking every reference,
  with no error anywhere. Key server records by a stable id you control, never by
  a client-supplied display name.
- **Config changes apply immediately** in RuneLite — no client restart. If a
  toggle appears to have no effect, it did not save.
- **Third-party plugins throw NPEs on startup constantly** (null widgets before
  login). When reading client logs, filter to your own package or you will spend
  the evening chasing other people's exceptions.

---

## Effort estimate

The integration studied implements per-skill XP push in roughly **40 lines** of
Java, following patterns already in that codebase. A purpose-built osrs-events
plugin is a larger job — loot events, a config panel, token handling, retry — but
it is a **days** project, not a months one. The client-side APIs are the easy
part; the real design work is tile matching and the trust model.

---

## Server API — built 2026-09-17

Three routes under `/api/plugin/v1`, all `Authorization: Bearer ose_…` (the
code from /settings/runelite), 60 requests a minute per code. While
`runelite_plugin_mode` is `off` all three answer **404**, token or not.

### `GET /events`

The player's joined, running events (not paused, closed, ended or upcoming)
and what each can complete right now:

- **Bingo:** every square with no claim yet from this competitor, not a
  wildcard, whose task links a wiki page.
- **Snakes & Ladders:** only the tile the player (or their team) stands on,
  if its task links a wiki page and it is not already claimed.

```json
{
  "mode": "testing",
  "rsn": "Iron Pondake",
  "proven": false,
  "events": [{ "id": "…", "title": "…", "type": "BINGO", "url": "…",
    "targets": [{ "kind": "bingo_square", "id": "…", "position": 4,
      "label": "Whip", "name": "Abyssal whip", "match": "abyssal whip",
      "min_quantity": 1, "required_count": 1 }] }],
  "watch": ["abyssal whip"]
}
```

`watch` is every `match` in one list: the plugin reports a drop or kill only
when its normalised name is in it.

`proven` says whether a client has ever reported this account logged in as
`rsn` — see `POST /identity`. False is not an error; it means every claim this
account makes goes to a host, screenshots included.

### `POST /identity`

```json
{ "rsn": "Iron Pondake" }
```

The character the client is signed in as. Send it once per connection, after
the local player is known.

```json
{ "rsn": "Iron Pondake", "matched": true, "proven": true }
```

`matched` is whether the reported character is the name on the account. A
match stamps `osrs_proven_at`; a mismatch changes nothing at all, because
somebody logging into their second character has not stopped owning the first.
Renaming the account on the site clears the proof — re-saving the same name
(the recheck button, the canonical-casing rewrite) does not.

**What this proves, exactly.** Somebody playing that character ran a client
holding this account's code. That rules out two accounts claiming one name by
accident, and it rules out typing a name you do not play. It does **not** rule
out forgery: this endpoint is an HTTP call, and a person with their own code
can post any name they like. It is the strongest thing worth asking a game
client for, not identity.

**What it is not.** `osrs_verified_at` is a different column answering a
different question — Wise Old Man has heard of the name. Any real name passes
that, including somebody else's.

### `POST /completions`

```json
{ "client_event_id": "uuid", "kind": "item|npc_kill", "name": "Prayer potion(4)",
  "quantity": 1, "rsn": "Iron Pondake", "occurred_at": "2026-09-17T12:00:00Z",
  "context": {
    "source": "npc_kill|loot|collection_log|kill_count",
    "npc_id": 415,
    "npc_name": "Abyssal demon",
    "npc_level": 124,
    "kill_count": 217,
    "region_id": 12441,
    "items": [{ "id": 4151, "name": "Abyssal whip", "quantity": 1 }]
  } }
```

- `201` with `{client_event_id, duplicate: false, claims: [...], progress:
  [...]}`. `claims` can be empty: nothing open matched. That is still
  recorded. `progress` is the counted targets this report moved **without**
  claiming: each entry is the target as `claims` describes it, plus `done`,
  the competitor's new total, against the target's own `required_count`. It
  is what lets the plugin say `Kurask 2 / 5` in game instead of staying
  silent until the fifth kill. Both lists are stored on the report, so a
  retry answers what the first attempt answered.
- `200` with `duplicate: true` and the original `claims` when
  `(account, client_event_id)` was seen before. Retrying is always safe.
- `422` when `rsn` is not the account's OSRS username (space, `_` and `-`
  compare equal, case ignored), the payload is invalid, or `context` (or an
  item in it) carries a key outside the ones listed above — added 2026-09-17
  so a client-side typo in a new field is a loud 422, not a silently dropped
  value. If you add a field to `context`, add it to the validation in
  `RunelitePluginController` in the same commit.
- `401` for an unknown code.

A claim is created the way the manual routes create one, with
`completed_via = RUNELITE`, no proof URL, and the status from
`initialClaimStatus('RUNELITE')`: approved only when the host does not review
claims or trusts RuneLite completions. Finishes and notifications run as
usual. `completed_at` is the server clock; `occurred_at` is only logged.
`quantity` is measured against the target's `min_quantity`, and a target
with a `required_count` above 1 is claimed only on the last of the reports it
asks for — see below.

`context` is optional and every field in it is optional — send whatever the
event actually had. It exists so a host reviewing a claim with no screenshot
sees more than "the plugin said so": what was killed and its level, the kill
count, everything else that dropped in the same kill, whether it came from
the collection log, and when. It is stored as-is on the `plugin_completions`
row and surfaced on the claim it created (`bingo_completions` /
`completed_tiles` now carry a `plugin_completion_id`). Deliberately nothing
sensitive goes in it: no chat log, no other players' names, and `region_id`
only — never exact coordinates.

### How a square is counted

Two numbers, two different questions, sent on every target and both applied
before a claim is made. They are independent and they stack: *"three drops of
at least twenty each"* is `required_count` 3 and `min_quantity` 20.

| Field | Default | Asks |
|---|---|---|
| `min_quantity` | 1 (any amount) | how big a single report has to be |
| `required_count` | 1 (the first one claims it) | how many qualifying reports it takes |

#### Stack size — `min_quantity`

A square or tile can say **"this only counts from N"** (`min_quantity`, 1 by
default — any amount). A report whose `quantity` is below the target's
`min_quantity` claims nothing, and that is not an error: the plugin reports
every drop, most of which are not the one.

It is **one report of at least N, never N reports adding up.** Tempoross drops
a Soaked page in stacks of varying size, and a clan that agreed only the 25
stack counted did not agree that twenty-five single pages count. There is no
accumulating variant.

The bar is sent on every target so the plugin can say what a square is still
waiting for, and it applies to a MANUAL claim too — the claim and review
dialogs print it, so a host judging a screenshot judges by the same number.

#### Repetitions — `required_count`

A square or tile can also say **"do this N times"** (`required_count`, 1 by
default — the first qualifying report claims it). Kill Zalcano five times, get
three clue scrolls. This is the mode a Tempoross square set to 3 was always
meant to be, and the one `min_quantity` cannot express.

Only reports that clear `min_quantity` count toward it, and nothing is claimed
until the last one — the claim, the notification and the finish all land on it.
Every report before that answers a `progress` entry instead of a claim, so the
plugin can announce each step.

**Counting is by distinct kill count, not by report.** Three Zalcano kills
arrive as three reports carrying `context.kill_count` 204, 205 and 206, and
that is what says they were three different kills: a report the plugin
replays or duplicates carries a kill count already counted and adds nothing.
A report with no kill count — a plain item drop has none — falls back to its
own report id, so each such report is its own event. Send `kill_count` in
`context` wherever the client has one; without it a repeated kill on the same
NPC cannot be told apart from a resend.

A report that happened before the event's start date counts for nothing, so a
client that was offline can send its backlog safely.

A report that counted for nothing — a kill already counted under a fresh
`client_event_id` — answers neither a claim nor a progress entry. Only a
report that moved the number is news.

Progress is per **competitor**: a team bingo counts the team's kills together,
a Snakes & Ladders tile counts the player board's. It is shown as `2 / 5` on
the square or tile and in the claim dialog, so a square three-fifths of the
way there says so instead of sitting silent until the fifth report.

### Name matching

Only the task **title** of a wiki-linked task counts (the square's override
label does not). Both sides go through `App\Support\RuneliteName::normalize`,
and the plugin must apply the same steps:

1. strip `<…>` tags
2. `_` and non-breaking space become a space
3. apostrophes (`'`, `’`, `‘`, `` ` ``) are removed
4. collapse whitespace, trim
5. drop a trailing **numeric** suffix: `Prayer potion(4)`, `Games necklace (8)`
6. lowercase

Non-numeric suffixes stay: `Clue scroll (medium)`, `Berserker ring (i)` and
`(uncharged)` are different items. `kind` is not used for matching yet; a
trigger column only gets added if the test set shows name alone is not enough.

The fixed test set is `RunelitePluginTestSeeder` (an unlisted 8×8 bingo card).
