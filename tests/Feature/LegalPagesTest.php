<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Page;
use App\Support\LegalPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The two pages that have to keep up with the code.
 *
 * `/privacy` and `/terms` are CMS rows like every other page, which is what
 * makes them quietly dangerous: PageSeeder plants them with firstOrCreate and
 * then never touches them again, so on any environment that has already run,
 * correcting the text in the repository changes nothing and reports nothing.
 * That is how the policy came to describe a Discord-only app months after
 * email accounts, an audit log and push notifications had shipped.
 *
 * So the tests here are about **reach and drift** rather than prose: that
 * there is a way to apply the repository's copy to a live database, that it
 * cannot quietly do the wrong thing, and that at least one number in the text
 * is tied to the config it claims to describe.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedPages(): void
    {
        foreach (LegalPages::SLUGS as $slug) {
            Page::create([
                'slug' => $slug,
                'title' => ucfirst($slug),
                'subtitle' => '',
                'is_published' => true,
                // Deliberately stale — the state every existing environment is
                // actually in.
                'blocks' => [['type' => 'prose', 'props' => ['text' => 'Old copy.']]],
            ]);
        }
    }

    #[Test]
    public function the_command_rewrites_a_page_that_already_exists(): void
    {
        $this->seedPages();

        $this->artisan('pages:sync-legal')->assertSuccessful();

        $this->assertSame(
            json_encode(LegalPages::privacy()),
            json_encode(Page::where('slug', 'privacy')->first()->blocks),
        );
    }

    /** Running it twice must report nothing to do, not rewrite twice. */
    #[Test]
    public function the_command_is_idempotent(): void
    {
        $this->seedPages();

        $this->artisan('pages:sync-legal')->assertSuccessful();

        $this->artisan('pages:sync-legal --diff')
            ->expectsOutputToContain('already match')
            ->assertSuccessful();
    }

    /** The rehearsal has to be a rehearsal. */
    #[Test]
    public function diff_writes_nothing(): void
    {
        $this->seedPages();

        $this->artisan('pages:sync-legal --diff')->assertSuccessful();

        $this->assertSame('Old copy.', Page::where('slug', 'privacy')->first()->blocks[0]['props']['text']);
    }

    /**
     * It overwrites page bodies, so the slug list is a guard rather than a
     * convenience: a typo must not become "rewrite everything".
     */
    #[Test]
    public function it_refuses_a_slug_that_is_not_a_legal_page(): void
    {
        $this->seedPages();

        $this->artisan('pages:sync-legal --slug=about')->assertFailed();
    }

    /** A database that never ran the seeder is told which command to run. */
    #[Test]
    public function a_missing_page_is_reported_rather_than_created(): void
    {
        $this->artisan('pages:sync-legal')
            ->expectsOutputToContain('PageSeeder')
            ->assertFailed();

        $this->assertSame(0, Page::whereIn('slug', LegalPages::SLUGS)->count());
    }

    /**
     * The one number in the policy that is a promise rather than a
     * description.
     *
     * The page states how long audit entries are kept, and that retention is
     * a config value somebody could change in an afternoon. Tying the two
     * together means the copy cannot drift away from the behaviour silently —
     * which is the exact failure this whole file exists to prevent.
     */
    #[Test]
    public function the_privacy_page_states_the_retention_period_the_app_actually_uses(): void
    {
        $text = json_encode(LegalPages::privacy());

        $this->assertStringContainsString(
            AuditLog::retentionDays().' days',
            $text,
            'The privacy page must state the audit retention period from config/audit.php.',
        );
    }

    /**
     * Things the app stores that the page has been wrong about before. Not a
     * style check — every one of these was a real gap found by rereading the
     * schema against the text on 2026-08-24.
     */
    #[Test]
    public function the_privacy_page_names_what_the_app_quietly_collects(): void
    {
        $text = json_encode(LegalPages::privacy());

        foreach (['IP address', 'user-agent', 'notifications', 'invite link', 'Discord server'] as $topic) {
            $this->assertStringContainsString($topic, $text, "The privacy page no longer mentions: {$topic}");
        }
    }

    /** What a host may do to the people in their event, which nothing said. */
    #[Test]
    public function the_terms_say_what_a_host_can_do_to_participants(): void
    {
        $text = json_encode(LegalPages::terms());

        $this->assertStringContainsString('OSRS account name', $text);
        $this->assertStringContainsString('deletes everybody', $text);
    }
}
