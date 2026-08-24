<?php

namespace App\Support;

/**
 * "3rd", not "3".
 *
 * One place rather than two, because the only interesting part of this — that
 * 11, 12 and 13 break the pattern every naive version follows — is exactly
 * the kind of rule that gets fixed in one copy and left wrong in the other.
 * Both a lost podium place and a final standing read better as words.
 */
final class Ordinal
{
    public static function of(int $number): string
    {
        $suffix = match (true) {
            // 11th, 12th, 13th — not 11st, 12nd, 13rd.
            in_array($number % 100, [11, 12, 13], true) => 'th',
            $number % 10 === 1 => 'st',
            $number % 10 === 2 => 'nd',
            $number % 10 === 3 => 'rd',
            default => 'th',
        };

        return $number.$suffix;
    }
}
