# 🗺️ OSRS Events — Roadmap

> Phase planning for future development. Active phase is tracked in [PROGRESS.md](./PROGRESS.md).

---


## Phase 3 — Polish & RuneLite Integration

- [ ] Pixel-art snake/ladder sprites overlaid on the board
- [ ] **RuneLite plugin webhook integration**
  - [ ] Webhook endpoint (`POST /webhook/runelite`)
  - [ ] Per-user auth token (shown in profile, regeneratable)
  - [ ] Auto-complete a tile when a matching RuneLite event is received
- [ ] Dark mode refinements (already available in Phase 1 via `UColorModeButton`)

---

## Phase 4 — Rebrand & Bingo Mode

- [ ] Full rebrand: "OSRS Snakes & Ladders" → **"OSRS Events"** (logo, SEO, copy)
- [ ] Proper logo and branding assets
- [ ] When creating a board/event, choose the **event type**:
  - Snakes & Ladders (existing)
  - Bingo (new)
- [ ] **Bingo board** — full implementation:
  - [ ] N×N grid (configurable) with free tile
  - [ ] Mark tiles as complete
  - [ ] Detect completed lines (horizontal, vertical, diagonal)
  - [ ] Full-board bingo detection
  - [ ] Points system: line bingo = X pts, full bingo = Y pts

---

## Phase 5 — More Event Types

> To be decided. Brainstorm options with the community and team.

Potential ideas:
- [ ] **Speed-run board**: first to reach the finish wins, no daily limit
- [ ] **OSRS Achievements race**: custom achievement unlock races
- [ ] **Skill-based challenges**: track XP drops or level milestones
- [ ] **Drop log events**: log rare drops, track via RuneLite webhook
