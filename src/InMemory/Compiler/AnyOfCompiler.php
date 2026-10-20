<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\AnyOf;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class AnyOfCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return AnyOf::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var AnyOf $criterion */
        $inner = array_map($root->compile(...), $criterion->criteria);

        return static fn (mixed $item): bool => array_any($inner, static fn (\Closure $predicate): bool => $predicate($item));
    }
}
