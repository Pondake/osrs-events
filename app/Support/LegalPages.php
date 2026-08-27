<?php

namespace App\Support;

/**
 * The text of `/privacy` and `/terms`, in one place.
 *
 * These are CMS pages like any other — an admin can edit them at admin →
 * Content — but they are the two whose text has to match what the code
 * actually does, and the code changes far more often than anybody remembers
 * to reread a policy. So the accurate version lives here, in the repository,
 * next to the schema it describes.
 *
 * **Two consumers, one source.** PageSeeder plants them on a fresh install;
 * `pages:sync-legal` applies them to a database where the rows already exist,
 * because the seeder uses firstOrCreate and will never touch an existing page.
 * Without that second route, editing this file would silently fix nothing on
 * any environment that had already run.
 *
 * Written from what the schema stores, checked against it on 2026-08-24. It is
 * still a legal document and still wants a human read before launch: being
 * accurate is the floor here, not the whole bar.
 */
final class LegalPages
{
    public const SLUGS = ['privacy', 'terms'];

    /** @return array<int, array<string, mixed>> */
    public static function privacy(): array
    {
        return [
            ['type' => 'prose', 'props' => ['text' => 'OSRS Events is run by one person as a free, ad-free hobby project. This page says exactly what it stores about you and why. Last updated August 2026.']],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'What we store'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Only what the site needs to work. Which of these exist depends on how you signed up:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => '**Discord ID, username and avatar** — if you log in with Discord, so we can recognise you and show you to your clan.'],
                        ['text' => '**Email address and a hashed password** — if you sign up directly instead. The password is hashed, never stored as you typed it.'],
                        ['text' => '**Your OSRS account name** — required, because skill races are scored from the public hiscores and those are looked up by name.'],
                        ['text' => '**A display name**, if you set one instead of using your Discord name.'],
                        ['text' => '**Your Discord servers**, cached from Discord when you log in, so events restricted to a server know whether you are in it.'],
                        ['text' => '**Event progress** — which events you joined, your position on a board, tiles you completed, and XP figures read from the hiscores.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Staying signed in'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Being logged in means a session record, and a session record includes **your IP address and your browser\'s user-agent string** alongside your account. It is deleted when you log out, and expires by itself if you do not come back. Admin actions are recorded with an IP address too — see below.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Notifications'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Turning on notifications registers **the device you turned them on from**. That means storing an address your browser hands out for this purpose, the keys needed to encrypt a message so only that device can read it, and the browser\'s user-agent string so you can tell your own devices apart in settings. Each device you enable is its own record, and you can remove any of them under Settings → Notifications.']],
                    ['type' => 'prose', 'props' => ['text' => 'A notification is delivered by the push service your browser belongs to — Google for Chrome, Apple for Safari, Mozilla for Firefox. **They carry the message, and they cannot read it**: it is encrypted with your device\'s keys before it leaves this server. They do learn that this site sent your device something, and when.']],
                    ['type' => 'prose', 'props' => ['text' => 'If your account has an email address, hosts of events you joined can also have the site email you when their event is paused, cancelled or started again. There is no marketing mail of any kind, and nothing else is ever sent to that address.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Teams and invites'],
                'blocks' => [
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => '**An invite link records who created it and who used it**, so a host can see who joined their event and revoke a link that is being passed around. Both survive the invite being used.'],
                        ['text' => '**A team can be tied to a Discord server**, and then stores that server\'s id and name so membership of it can be checked.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'What we do not do'],
                'blocks' => [
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'We do not read your Discord messages. The login only ever asks for your identity and your server list.'],
                        ['text' => 'We do not run analytics, advertising or third-party tracking of any kind.'],
                        ['text' => 'We do not sell or share your data with anyone.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Admin actions are logged'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Actions taken by site admins — granting a role, deleting an account — are recorded in an audit log so there is a record of who did what. An entry keeps the display name of the account it was about **even after that account is deleted**, because an entry that reads "deleted user" is not a record of anything. It also stores the IP address the action came from.']],
                    ['type' => 'prose', 'props' => ['text' => '**Entries are deleted after 90 days.** That is the whole retention period, and it is the reason the paragraph above is a limit rather than an open ending.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Other services'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'A few outside services are involved, and each has its own policy:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => '**Discord**, for logging in — see [their privacy policy](https://discord.com/privacy).'],
                        ['text' => '**[Wise Old Man](https://wiseoldman.net)**, for reading OSRS hiscores. We send them the OSRS account name you gave us so they can return your XP. Nothing else about you is sent.'],
                        ['text' => '**Your browser\'s push service** — Google, Apple or Mozilla — but only if you turn notifications on, and only ever carrying a message they cannot read.'],
                    ]]],
                    ['type' => 'prose', 'props' => ['text' => 'One more, only where a host has set it up: an event can be pointed at a **Discord webhook**, and when it is, that event\'s title and whether it has been paused or cancelled are posted into that server. That is a destination the host chooses, not us.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Your data is yours'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'You can change your display name and OSRS username yourself under Settings, choose which notifications you get, and remove any device you registered. You can also **delete your account yourself**, under Settings → Account — nobody has to approve it and you do not have to ask anyone. It takes your progress with it, and the page tells you what happens to any event you host before you confirm.']],
                    ['type' => 'prose', 'props' => ['text' => 'If you cannot reach that page — you have lost access to the account, or something is broken — mail us and we will do it for you.']],
                    ['type' => 'links', 'props' => ['links' => [
                        ['label' => 'Delete your account', 'to' => '/settings/account', 'icon' => 'i-lucide-trash-2', 'variant' => 'outline'],
                        ['label' => 'Ask us to do it', 'to' => 'mailto:dev@absolit.nl', 'icon' => 'i-lucide-mail', 'variant' => 'ghost'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'callout',
                'props' => [
                    'color' => 'neutral',
                    'icon' => 'i-lucide-info',
                    'title' => 'Changes to this policy',
                    'description' => 'If what we store changes, this page changes with it. It is dated at the top so you can tell when it last did.',
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function terms(): array
    {
        return [
            ['type' => 'prose', 'props' => ['text' => 'Using OSRS Events means accepting what is on this page. It is a free hobby project, and these terms are written to match that rather than to sound like a law firm. Last updated August 2026.']],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Your account'],
                'blocks' => [
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'Sign in with Discord or with an email address — either is fine.'],
                        ['text' => 'One account per person. Extra accounts made to enter an event twice will be removed.'],
                        ['text' => 'Give your real OSRS account name. Events are scored from the hiscores, so a wrong name only means you score nothing.'],
                        ['text' => 'You are responsible for what happens under your account.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Joining somebody else\'s event'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'An event has a host, and joining one gives that host a say over your participation in it. They can:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'See your display name and your OSRS account name, and your progress in their event.'],
                        ['text' => 'Approve or reject the squares you claim, and remove you from the event.'],
                        ['text' => 'Pause, cancel or delete the event — which deletes everybody\'s progress in it, including yours.'],
                        ['text' => 'Point the event at a Discord server, where its title and status are then posted.'],
                    ]]],
                    ['type' => 'prose', 'props' => ['text' => 'None of that reaches beyond their event. A host is not an administrator: they cannot see your email address, your other events, or anything about your account you did not bring into theirs.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Running one'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'The other side of the same paragraph. If you host an event, the people in it have trusted you with the list above — so run it the way you would want somebody else to run one you had joined, and do not use what you can see there for anything other than running it.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'Fair use'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'Behave the way you would want everyone else to. Specifically, do not:']],
                    ['type' => 'list', 'props' => ['items' => [
                        ['text' => 'Harass, abuse or impersonate other players.'],
                        ['text' => 'Scrape the site or hammer it with automated requests.'],
                        ['text' => 'Exploit bugs instead of reporting them. Reporting one is genuinely appreciated.'],
                        ['text' => 'Falsify event progress or claim tiles you did not complete.'],
                    ]]],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'section',
                'props' => ['title' => 'No guarantees'],
                'blocks' => [
                    ['type' => 'prose', 'props' => ['text' => 'This is provided as-is. It may go down, lose data, or change without warning, and it depends on outside services — Discord for login, the OSRS hiscores through Wise Old Man for scoring — that can be unavailable on their own schedule. Accounts that break these terms can be removed.']],
                ],
            ],
            ['type' => 'separator', 'props' => []],
            [
                'type' => 'callout',
                'props' => [
                    'color' => 'warning',
                    'icon' => 'i-lucide-alert-triangle',
                    'title' => 'Not affiliated with Jagex',
                    // Exact wording required by Jagex's Fan Content Policy §8.1,
                    // not a paraphrase — the policy specifies this sentence
                    // verbatim as the required attribution, not just "say
                    // something like this". Kept in step with the same
                    // sentence in the site-wide footer (common.not_affiliated).
                    'description' => "Created using intellectual property belonging to Jagex Limited under the terms of Jagex's Fan Content Policy. This content is not endorsed by or affiliated with Jagex.",
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>> the blocks for one legal slug
     */
    public static function blocksFor(string $slug): array
    {
        return match ($slug) {
            'privacy' => self::privacy(),
            'terms' => self::terms(),
        };
    }
}
