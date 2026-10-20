<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Sidus\FilterBundle\Criterion\Criterion;

/**
 * Dispatches a criterion to the compiler registered for its class. Built once per factory, immutable.
 */
final readonly class InMemoryCompiler
{
    /** @var array<class-string<Criterion>, InMemoryCriterionCompilerInterface> */
    private array $compilers;

    /**
     * @param iterable<InMemoryCriterionCompilerInterface> $compilers
     */
    public function __construct(iterable $compilers, public ValueReader $reader = new ValueReader())
    {
        $map = [];
        foreach ($compilers as $compiler) {
            $map[$compiler::getCriterionClass()] = $compiler;
        }
        $this->compilers = $map;
    }

    public function compile(Criterion $criterion): \Closure
    {
        $compiler = $this->compilers[$criterion::class] ?? throw new \LogicException(\sprintf('The in_memory provider has no compiler for criterion %s.', $criterion::class));

        return $compiler->compile($criterion, $this);
    }
}
