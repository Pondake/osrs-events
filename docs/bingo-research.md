# What a real OSRS clan bingo needs

Researched 2026-08-21 against how clans actually run these, rather than
designing from the word "bingo". Sources: The Birdhouse's and AIO Bingo's
run-an-event guides, OSRSHub's rules/verification write-up, and the feature
lists of the established trackers (osrsbingohub, aiobingo, rune-bingo,
osrs-tracker, Slepe).

## What the trackers actually do

| Requirement | Detail found | Status here |
|---|---|---|
| **Approval workflow** | Players submit proof, hosts approve or reject from a queue. "Approved tiles update the leaderboard instantly." | **Was missing** — built |
| **Tile states** | done, not-done, **pending**, and **disputed** as a fourth | **Was binary** — built |
| **Screenshot proof** | Chat drop message or collection-log slot, username visible, timestamp inside the event window, uncropped chatbox | **Was missing** — built |
| **Points per tile** | "points per tile + bonus points per completed row, column or diagonal" | **Was missing** — built |
| **Tile weighting** | "1 x Zulrah = 5 pts, 5 x Zulrah = 15 pts" — harder tiles score more | **Was missing** — built |
| **Board sizes** | 5x5 for 2-3 days, 7x7 for a week, **10x10** for competitive | Capped at 7 — raised |
| **Team attribution** | "Drops only count for the team of the player who received them" | Already correct |
| **Splitting within a team** | Allowed; trading between teams is not | Already correct (team-scoped completions) |
| **One drop, one tile** | "A drop will not count towards multiple tiles" | **Not enforceable** — see below |
| **Hard cutoff** | Submission time logged; late submissions blocked | Built |

## What is deliberately not built

**"A drop counts for only one tile"** cannot be enforced without knowing which
in-game item a submission refers to, which needs either item-level tile
definitions or the RuneLite plugin. It is a moderation rule here: the host
sees the proof and rejects a double-claim. Recorded rather than faked, because
a checkbox that claims to enforce it would be worse than a host who knows they
have to look.

**Automatic verification from the hiscores** is what the RuneLite plugin in the
backlog is for. Skill races already read the hiscores; bingo tiles are drops
and collection-log slots, which the hiscores do not expose.

## The table question, reconsidered

Asked to reconsider reusing `boards`/`tiles` for bingo. Re-evaluated with the
requirements above in hand, and the answer is **more strongly separate than
before**, because the research shows the two grids diverging rather than
converging.

To share one table, `tiles` would need these columns, every one of them
meaningless for Snakes & Ladders:

* `points` — bingo weights tiles; a S&L tile has no score
* `status` — pending/approved/rejected; a S&L tile is complete or not
* `proof_url`, `submitted_by`, `reviewed_by`, `reviewed_at` — no S&L equivalent

and `boards` would carry `win_condition` (meaningless with a dice), while
bingo would carry `dice_roll_limit` (meaningless without one). The completion
tables differ more fundamentally still: `player_boards` is one row per player
holding a **position**, where a bingo completion belongs to a **team** and
holds a review state.

What the two genuinely share is `Task` — the library of "kill 50 cows" — and
that **is** shared already.
