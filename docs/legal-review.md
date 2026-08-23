# Privacy and terms — what the pages do not yet say

A draft, not a change. `/privacy` and `/terms` are CMS pages (admin →
Content), and the backlog asks for a read-through before launch rather than a
patch — so nothing here has been written to those pages. Each item below is
what the code actually does today, with the table or file it lives in, plus
wording that can be pasted in if you agree with it.

Checked 2026-08-24 against the current schema and the current app.

## What `/privacy` already covers, correctly

Discord identity and avatar; email address and hashed password; the OSRS
account name and why it is required; the display name; the cached Discord
server list; event progress; the audit log keeping a deleted account's
display name; Discord and Wise Old Man as the two outside services; deletion
on request. None of that needs changing.

## Missing — things the app stores that the page does not mention

**1. Sessions record an IP address and a browser string.**
`sessions.ip_address` and `sessions.user_agent`, written on every request by
Laravel's own session driver (the table is in the first migration). The audit
log stores an IP as well — `audit_logs.ip_address`, on every recorded admin
action. Both are personal data by any reading, and neither is named.

> **Sessions.** Staying signed in means a session record: your account, your
> IP address and your browser's user-agent string. It is deleted when you log
> out, and expires on its own if you do not come back.

**2. The audit log has a retention period, and it is a number.**
Ninety days, from `config/audit.php`, pruned by `AuditLog::prunable()`. The
page says entries are kept and deliberately outlive a deleted account. Saying
for how long is the part that turns that from a warning into a commitment.

> Audit entries are pruned after 90 days.

**3. Invite records name two people.**
`board_invites.created_by`, and `board_accesses` records who used it. So an
invite says who handed it out and who accepted it, and both outlive the
invite's use.

> **Invites.** An invite link records who created it and who used it, so a
> host can see who joined their event and revoke a link that is being passed
> around.

**4. Emails about events you joined.** *(New — 2026-08-23.)*
`EventNotificationService` mails every participant with an address when an
event is paused, resumed, cancelled or restored. That is a new purpose for
the email address, which the page currently justifies only as a login
credential and a recovery route. This one genuinely changes what the page
promises.

> **Event emails.** If your account has an email address, hosts of events you
> joined can have the site tell you when their event is paused, cancelled or
> started again. There is no marketing mail of any kind, and nothing else is
> ever sent to that address.

**5. A team can be tied to a Discord server.**
`teams.guild_id` and `teams.guild_name`. Small, but it is a link between an
account's clan and its Discord server, stored on our side.

**6. Discord announcements, when they are switched on.** *(Currently off.)*
With the site setting enabled, a host can point an event at a Discord webhook
and the event's title and status are posted into that server. That is data
leaving to a third party chosen by the host rather than by us, which is
exactly the kind of thing a policy has to state before it happens — worth
writing the paragraph at the same time the switch is turned on, not after.

## `/terms` — one gap worth naming

The page covers accounts, fair use, no guarantees, and the Jagex disclaimer.
What it does not say is what a **host** may do with the people in their event:
hosts can see participants' display names and OSRS names, remove people,
delete an event along with everyone's progress, and (once webhooks are on)
push its status into a Discord server. "Running an event means you can see and
affect other people's participation" is the sentence that is missing.

## Also worth a look while you are in there

- Both pages say "Last updated August 2026" in the opening paragraph. If any
  of the above goes in, that line moves too.
- `/terms` has never had a read-through on this stack at all — the backlog
  has said so since 2026-08-21 and it is still true.
