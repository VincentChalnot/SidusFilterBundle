<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\InMemory\Comparison;
use Sidus\FilterBundle\InMemory\ValueReader;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class RangeCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return Range::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var Range $criterion */
        $reader = $root->reader;

        return static fn (mixed $item): bool => array_any(
            ValueReader::flatten($reader->read($item, $criterion->field)),
            static function (mixed $value) use ($criterion): bool {
                if (null !== $criterion->min) {
                    $c = Comparison::compare($value, $criterion->min);
                    if (null === $c || $c < 0 || (0 === $c && !$criterion->minInclusive)) {
                        return false;
                    }
                }
                if (null !== $criterion->max) {
                    $c = Comparison::compare($value, $criterion->max);
                    if (null === $c || $c > 0 || (0 === $c && !$criterion->maxInclusive)) {
                        return false;
                    }
                }

                return true;
            },
        );
    }
}
