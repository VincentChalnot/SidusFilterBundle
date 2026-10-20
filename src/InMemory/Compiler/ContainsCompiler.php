<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Contains;

final class ContainsCompiler extends TextCompiler
{
    public static function getCriterionClass(): string
    {
        return Contains::class;
    }

    protected function test(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }
}
