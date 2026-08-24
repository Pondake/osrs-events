<?php

namespace App\Support;

/**
 * One notification, as it will look on a lock screen.
 *
 * A DTO rather than an array so that the three fields that actually decide
 * behaviour cannot be forgotten silently:
 *
 *  - `path` is a **path, not a URL**. The service worker resolves it against
 *    its own scope, so a full URL baked in here would point at whatever host
 *    generated it — which is the wrong one the moment anything runs behind a
 *    different domain, and unverifiable until somebody taps it.
 *  - `tag` collapses: a second notification with the same tag replaces the
 *    first rather than stacking under it. Ten claims reviewed in a minute
 *    should be one line on the lock screen, not ten.
 *  - `category` is carried through to the client so a notification can say
 *    which switch turns it off. Nothing is more likely to get permission
 *    revoked than a notification with no visible way to stop it.
 */
final class PushMessage
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $path,
        public readonly string $category,
        /**
         * Same tag replaces rather than stacks. Defaults to the category,
         * which is the right grain for most: per-event tags are worth passing
         * explicitly when two events could reasonably notify at once.
         */
        public readonly ?string $tag = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'path' => $this->path,
            'category' => $this->category,
            'tag' => $this->tag ?? $this->category,
        ];
    }
}
