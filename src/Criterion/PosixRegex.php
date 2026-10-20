<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/**
 * The POSIX ERE subset of {@see Matches}: validation and translation to PCRE (the reference implementation).
 */
final class PosixRegex
{
    private const int MAX_LENGTH = 255;

    /** @return string|null the reason the pattern is outside the portable subset, null when valid */
    public static function validate(string $pattern): ?string
    {
        if ('' === $pattern || \strlen($pattern) > self::MAX_LENGTH) {
            return \sprintf('A pattern must be 1 to %d bytes long.', self::MAX_LENGTH);
        }
        if (!mb_check_encoding($pattern, 'UTF-8')) {
            return 'The pattern is not valid UTF-8.';
        }
        if (str_contains($pattern, '(?')) {
            return 'Lookaround, named groups and inline flags are not portable.';
        }
        if (1 === preg_match('/\\\\[A-Za-z0-9]/', preg_replace('/\\\\\\\\/', '', $pattern))) {
            return 'Backreferences and escape classes (\d, \w, ...) are not portable; use [[:digit:]] or escape punctuation only.';
        }
        if (1 === preg_match('/[*+?}]\+/', $pattern)) {
            return 'Possessive quantifiers are not portable.';
        }
        if (false === @preg_match(self::toPcre($pattern, true), '')) {
            return 'The pattern is not a valid regular expression.';
        }

        return null;
    }

    public static function toPcre(string $pattern, bool $caseSensitive): string
    {
        return '~'.str_replace('~', '\~', $pattern).'~u'.($caseSensitive ? '' : 'i');
    }

    public static function matches(string $pattern, bool $caseSensitive, string $subject): bool
    {
        return 1 === preg_match(self::toPcre($pattern, $caseSensitive), $subject);
    }
}
