<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class NotCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return Not::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var Not $criterion */
        $inner = $root->compile($criterion->criterion);

        return static fn (mixed $item): bool => !$inner($item);
    }
}
