<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\In;
use Sidus\FilterBundle\InMemory\Comparison;
use Sidus\FilterBundle\InMemory\ValueReader;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class InCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return In::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var In $criterion */
        $reader = $root->reader;

        return static fn (mixed $item): bool => array_any(
            ValueReader::flatten($reader->read($item, $criterion->field)),
            static fn (mixed $value): bool => array_any(
                $criterion->values,
                static fn (mixed $candidate): bool => Comparison::equals($value, $candidate),
            ),
        );
    }
}
