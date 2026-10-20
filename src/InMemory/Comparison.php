<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

/**
 * The reference value semantics (section 4): strings case-sensitive and compared byte-wise, numbers numerically,
 * dates as instants. Null never matches.
 */
final class Comparison
{
    public static function equals(mixed $a, mixed $b): bool
    {
        if (null === $a || null === $b) {
            return false;
        }
        [$a, $b] = self::align($a, $b);

        return match (true) {
            $a instanceof \DateTimeInterface && $b instanceof \DateTimeInterface => $a == $b,
            \is_int($a) || \is_float($a) => (\is_int($b) || \is_float($b)) && $a == $b,
            default => $a === $b,
        };
    }

    /** @return int|null -1, 0, 1, or null when the values are not comparable (never matches) */
    public static function compare(mixed $a, mixed $b): ?int
    {
        if (null === $a || null === $b) {
            return null;
        }
        [$a, $b] = self::align($a, $b);
        if ($a instanceof \DateTimeInterface && $b instanceof \DateTimeInterface) {
            return $a <=> $b;
        }
        if ((\is_int($a) || \is_float($a)) && (\is_int($b) || \is_float($b))) {
            return $a <=> $b;
        }
        if (\is_string($a) && \is_string($b)) {
            return strcmp($a, $b) <=> 0;
        }

        return null;
    }

    /** Total order for sorting: null first, then comparable values, then by type name and string form. */
    public static function sort(mixed $a, mixed $b): int
    {
        if (null === $a || null === $b) {
            return (null === $a ? 0 : 1) <=> (null === $b ? 0 : 1);
        }

        return self::compare($a, $b) ?? strcmp(self::fallback($a), self::fallback($b)) <=> 0;
    }

    /**
     * Lets a number meet a numeric string and a date meet an ISO date string; anything else is left alone.
     *
     * @return array{0: mixed, 1: mixed}
     */
    private static function align(mixed $a, mixed $b): array
    {
        $isNumber = static fn (mixed $v): bool => \is_int($v) || \is_float($v);
        if ($isNumber($a) && \is_string($b) && is_numeric($b)) {
            $b += 0;
        } elseif ($isNumber($b) && \is_string($a) && is_numeric($a)) {
            $a += 0;
        } elseif ($a instanceof \DateTimeInterface && \is_string($b)) {
            $b = self::date($b) ?? $b;
        } elseif ($b instanceof \DateTimeInterface && \is_string($a)) {
            $a = self::date($a) ?? $a;
        }

        return [$a, $b];
    }

    private static function date(string $value): ?\DateTimeImmutable
    {
        if (1 !== preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    private static function fallback(mixed $value): string
    {
        return get_debug_type($value).':'.(\is_scalar($value) || $value instanceof \Stringable ? (string) $value : (\is_object($value) ? spl_object_id($value) : ''));
    }
}
