<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\AllOf;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class AllOfCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return AllOf::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var AllOf $criterion */
        $inner = array_map($root->compile(...), $criterion->criteria);

        return static fn (mixed $item): bool => array_all($inner, static fn (\Closure $predicate): bool => $predicate($item));
    }
}
