<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\InMemory;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\ArraySource;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Test\Contract\AbstractProviderContractTestCase;
use Sidus\FilterBundle\Test\Contract\FixtureDataset;

/**
 * The reference provider: passes the whole contract suite with no known divergence.
 */
final class InMemoryProviderTest extends AbstractProviderContractTestCase
{
    protected function providerName(): string
    {
        return InMemoryQueryHandlerFactory::PROVIDER;
    }

    protected function listOptions(): array
    {
        return ['source' => 'posts', 'identifier' => 'id'];
    }

    protected function createHandler(ListDefinition $definition, ?CountStrategyInterface $countStrategy = null): QueryHandlerInterface
    {
        return InMemoryQueryHandlerFactory::standalone(['posts' => new ArraySource(FixtureDataset::posts())])
            ->createQueryHandler($definition, FilterTypeRegistry::builtIn(), $countStrategy);
    }
}
