<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class IsNullCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return IsNull::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var IsNull $criterion */
        $reader = $root->reader;

        return static function (mixed $item) use ($reader, $criterion): bool {
            $values = $reader->read($item, $criterion->field);

            return [] === $values || array_any($values, static fn (mixed $value): bool => null === $value);
        };
    }
}
