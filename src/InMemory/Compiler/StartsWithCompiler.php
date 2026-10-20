<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\StartsWith;

final class StartsWithCompiler extends TextCompiler
{
    public static function getCriterionClass(): string
    {
        return StartsWith::class;
    }

    protected function test(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }
}
