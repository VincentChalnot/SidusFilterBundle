<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\InMemory\ValueReader;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class IsEmptyCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return IsEmpty::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var IsEmpty $criterion */
        $reader = $root->reader;

        return static function (mixed $item) use ($reader, $criterion): bool {
            $values = $reader->read($item, $criterion->field);

            return [] === $values || array_any($values, static fn (mixed $value): bool => null === $value
                || '' === $value
                || (ValueReader::isCollection($value) && [] === [...$value]));
        };
    }
}
