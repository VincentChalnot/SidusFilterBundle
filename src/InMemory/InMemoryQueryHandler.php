<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Provider\AbstractQueryHandler;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryContextInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

final class InMemoryQueryHandler extends AbstractQueryHandler
{
    public function __construct(
        ListDefinition $definition,
        FilterTypeRegistry $filterTypes,
        ?CountStrategyInterface $countStrategy,
        private readonly InMemorySourceInterface $source,
        private readonly InMemoryCompiler $compiler,
    ) {
        parent::__construct($definition, $filterTypes, $countStrategy);
    }

    protected function createContext(): QueryContextInterface
    {
        return new InMemoryContext($this->source->items());
    }

    protected function applyCriterion(QueryContextInterface $context, Criterion $criterion): void
    {
        \assert($context instanceof InMemoryContext);
        $context->addPredicate($this->compiler->compile($criterion));
    }

    protected function applySort(QueryContextInterface $context, Sort $sort): void
    {
        \assert($context instanceof InMemoryContext);
        $comparator = $this->compiler->compile($sort);
        $identifier = new Field($this->definition->options['identifier']);
        $reader = $this->compiler->reader;
        $context->sortBy(static fn (mixed $a, mixed $b): int => $comparator($a, $b)
            ?: Comparison::sort($reader->read($a, $identifier)[0] ?? null, $reader->read($b, $identifier)[0] ?? null));
    }

    protected function fetch(QueryContextInterface $context, int $offset, int $limit): iterable
    {
        \assert($context instanceof InMemoryContext);

        return \array_slice($context->matching(), $offset, $limit);
    }

    protected function countNative(QueryContextInterface $context): int
    {
        \assert($context instanceof InMemoryContext);

        return \count($context->matching());
    }
}
