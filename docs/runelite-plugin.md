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
