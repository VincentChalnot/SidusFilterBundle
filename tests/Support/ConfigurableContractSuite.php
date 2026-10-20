<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\ArraySource;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Test\Contract\AbstractProviderContractTestCase;

/**
 * An in-memory contract suite whose storage and declared divergences are set by the test (never run by PHPUnit itself).
 */
final class ConfigurableContractSuite extends AbstractProviderContractTestCase
{
    /** @var list<array<string, mixed>> */
    public array $data = [];

    /** @var array<string, string> */
    public array $divergences = [];

    protected function providerName(): string
    {
        return 'in_memory';
    }

    protected function listOptions(): array
    {
        return ['source' => 'posts', 'identifier' => 'id'];
    }

    protected function knownDivergences(): array
    {
        return $this->divergences;
    }

    protected function createHandler(ListDefinition $definition, ?CountStrategyInterface $countStrategy = null): QueryHandlerInterface
    {
        return InMemoryQueryHandlerFactory::standalone(['posts' => new ArraySource($this->data)])
            ->createQueryHandler($definition, FilterTypeRegistry::builtIn(), $countStrategy);
    }
}
