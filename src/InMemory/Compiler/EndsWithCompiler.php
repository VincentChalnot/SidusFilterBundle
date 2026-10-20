<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\EndsWith;

final class EndsWithCompiler extends TextCompiler
{
    public static function getCriterionClass(): string
    {
        return EndsWith::class;
    }

    protected function test(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }
}
