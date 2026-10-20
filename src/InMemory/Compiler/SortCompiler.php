<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Criterion\SortDirection;
use Sidus\FilterBundle\InMemory\Comparison;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

/**
 * Compiles to a comparator. Nulls sort first ascending, last descending; strings byte-wise; a to-many column sorts
 * by its first element.
 */
final class SortCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return Sort::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var Sort $criterion */
        $reader = $root->reader;

        return static function (mixed $a, mixed $b) use ($criterion, $reader): int {
            foreach ($criterion->columns as $column) {
                $order = Comparison::sort(
                    $reader->read($a, $column->field)[0] ?? null,
                    $reader->read($b, $column->field)[0] ?? null,
                );
                if (0 !== $order) {
                    return SortDirection::Desc === $column->direction ? -$order : $order;
                }
            }

            return 0;
        };
    }
}
