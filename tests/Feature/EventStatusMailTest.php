<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EventStatusChanged;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the "your event was paused / finished / cancelled…" mail looks like.
 *
 * Who it is sent to is EventPauseTest's and EventFinishTest's business; this
 * is the mail itself, rendered, because every part of it that matters is a
 * part that silently degrades — a missing label falls back to the raw key, a
 * host's note pasted into Markdown turns into a link, a cancelled event gets
 * a button to a page that is gone.
 */
class EventStatusMailTest extends TestCase
{
    private const URL = 'https://osrs-events.test/events/abc';

    private function render(string $change, ?string $reason = null, ?string $url = self::URL, string $title = 'Halloween Snakes & Ladders'): string
    {
        return (string) (new EventStatusChanged($change, $title, $url, $reason, 'SNAKES_LADDERS'))
            ->toMail(new User(['nickname' => 'Zezima']))
            ->render();
    }

    public static function changes(): array
    {
        return [
            'paused' => [EventStatusChanged::PAUSED],
            'resumed' => [EventStatusChanged::RESUMED],
            'ended' => [EventStatusChanged::ENDED],
            'reopened' => [EventStatusChanged::REOPENED],
            'cancelled' => [EventStatusChanged::CANCELLED],
            'restored' => [EventStatusChanged::RESTORED],
        ];
    }

    /** Every change has its own label, line, preheader and subject — none falls back to a key. */
    #[Test]
    #[DataProvider('changes')]
    public function every_change_reads_as_words(string $change): void
    {
        $mail = (new EventStatusChanged($change, 'Halloween Snakes & Ladders', self::URL, null, 'BINGO'))
            ->toMail(new User(['nickname' => 'Zezima']));
        $html = (string) $mail->render();

        $this->assertStringNotContainsString('notifications.', $mail->subject);
        $this->assertStringNotContainsString('notifications.', $html);
        $this->assertStringContainsString('Hi Zezima,', $html);
        $this->assertStringContainsString('Halloween Snakes &amp; Ladders', $html);
        $this->assertStringContainsString(e(trans("notifications.event_{$change}_label")), $html);
        $this->assertStringContainsString(e(trans('events.type_bingo')), $html);
    }

    /** Two changes that both mean "it runs again" must not share a subject line. */
    #[Test]
    public function resumed_and_reopened_can_be_told_apart_in_an_inbox(): void
    {
        $this->assertNotSame(
            trans('notifications.event_resumed_subject', ['event' => 'X']),
            trans('notifications.event_reopened_subject', ['event' => 'X']),
        );
    }

    #[Test]
    public function the_hosts_note_is_quoted_and_is_the_inbox_preview(): void
    {
        $html = $this->render(EventStatusChanged::PAUSED, 'Wise Old Man is down, back tonight.');

        $this->assertStringContainsString(e(trans('notifications.event_reason_label')), $html);
        // Once in the hidden preheader, once in the quote.
        $this->assertSame(2, substr_count($html, 'Wise Old Man is down, back tonight.'));
    }

    /**
     * A host's note and a title are typed by a person, and the mail is
     * Markdown. They must arrive as typed: no link from link syntax, and a
     * blank line in the note must not end the quote and spill into the mail.
     */
    #[Test]
    public function what_a_host_typed_is_not_read_as_markdown(): void
    {
        $html = $this->render(
            EventStatusChanged::PAUSED,
            "Back soon.\n\n[click me](https://evil.example)",
            title: '**Loud** [title](https://evil.example)',
        );

        $this->assertStringNotContainsString('href="https://evil.example"', $html);
        $this->assertStringContainsString('[click me](https://evil.example)', $html);
        $this->assertStringContainsString('**Loud**', $html);
    }

    /** The page is gone, so the button goes to the events that are still on. */
    #[Test]
    public function a_cancelled_event_points_at_the_events_list(): void
    {
        $html = $this->render(EventStatusChanged::CANCELLED, url: null);

        $this->assertStringContainsString('href="'.route('events.index').'"', $html);
        $this->assertStringNotContainsString(self::URL, $html);
    }

    #[Test]
    public function the_footer_says_why_and_whose_it_is_not(): void
    {
        $html = $this->render(EventStatusChanged::ENDED);

        $this->assertStringContainsString(e(trans('notifications.event_footer', ['event' => 'Halloween Snakes & Ladders'])), $html);
        $this->assertStringContainsString('Jagex', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
    }
}
