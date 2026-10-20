<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

/**
 * A service a filter type depends on.
 */
final class Prefixer
{
    public function accepts(string $value): bool
    {
        return 'forbidden' !== $value;
    }

    public function apply(string $value): string
    {
        return $value.' again';
    }
}
