<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Provider\CriterionCompilerInterface;

/**
 * Compiles one criterion class to a predicate `Closure(item): bool`; `Sort` compiles to a comparator
 * `Closure(a, b): int`. Composites compile their children through `$root`.
 */
interface InMemoryCriterionCompilerInterface extends CriterionCompilerInterface
{
    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure;
}
