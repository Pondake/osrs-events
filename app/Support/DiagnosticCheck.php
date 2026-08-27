<?php

namespace App\Support;

/**
 * One line on the diagnostics page: a question, its answer, and how bad the
 * answer is.
 *
 * Four levels, and the distinction that matters most is **fail vs warn**:
 *
 *  - `fail`  — this is broken right now and something a user expects is not
 *              happening.
 *  - `warn`  — it works, but it is one step away from not working, or it is
 *              lying (a mailer that logs and reports success).
 *  - `ok`    — checked, and fine.
 *  - `info`  — a fact worth seeing that cannot be right or wrong.
 *
 * `info` exists so that context does not have to be dressed up as a verdict.
 * "3 devices registered" is neither good nor bad, and colouring it green
 * would make the page look like it had checked something it had not.
 */
final class DiagnosticCheck
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    public const INFO = 'info';

    public function __construct(
        public readonly string $label,
        public readonly string $status,
        /** What the answer actually was. Never a secret — see the group docs. */
        public readonly string $detail,
        /** What to do about it, when there is something to do. */
        public readonly ?string $fix = null,
        /**
         * A stable identifier the frontend can key off of, for the rare check
         * that offers more than a status line — e.g. `wom_standings`, whose
         * "N failing to sync" wants a details modal, not just a fix sentence.
         * Null for every ordinary check: matching on this only makes sense
         * for the handful that actually have something behind them.
         */
        public readonly ?string $key = null,
    ) {}

    public static function ok(string $label, string $detail): self
    {
        return new self($label, self::OK, $detail);
    }

    public static function warn(string $label, string $detail, ?string $fix = null, ?string $key = null): self
    {
        return new self($label, self::WARN, $detail, $fix, $key);
    }

    public static function fail(string $label, string $detail, ?string $fix = null, ?string $key = null): self
    {
        return new self($label, self::FAIL, $detail, $fix, $key);
    }

    public static function info(string $label, string $detail): self
    {
        return new self($label, self::INFO, $detail);
    }

    /** Shorthand for the common "this boolean decides ok or fail" shape. */
    public static function when(bool $passed, string $label, string $okDetail, string $failDetail, ?string $fix = null): self
    {
        return $passed ? self::ok($label, $okDetail) : self::fail($label, $failDetail, $fix);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'status' => $this->status,
            'detail' => $this->detail,
            'fix' => $this->fix,
            'key' => $this->key,
        ];
    }
}
