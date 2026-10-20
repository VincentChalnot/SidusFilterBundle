<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Registry;

use Psr\Container\ContainerInterface;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Exception\UnknownProviderException;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;

/**
 * The entry point: a new, single-use query handler for every call. Holds no per-request state; provider factories
 * are indexed by name once, from the tagged iterator.
 */
final class QueryHandlerRegistry
{
    /** @var array<string, QueryHandlerFactoryInterface>|null */
    private ?array $factories = null;

    /**
     * @param iterable<QueryHandlerFactoryInterface> $factorySource
     * @param ContainerInterface                     $countStrategies CountStrategyInterface services by id (those named by lists)
     */
    public function __construct(
        private readonly ListDefinitionRegistry $definitions,
        private readonly iterable $factorySource,
        private readonly FilterTypeRegistry $filterTypes,
        private readonly ContainerInterface $countStrategies,
    ) {
    }

    public function has(string $code): bool
    {
        return $this->definitions->has($code);
    }

    public function getDefinition(string $code): ListDefinition
    {
        return $this->definitions->get($code);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return $this->definitions->codes();
    }

    public function createQueryHandler(string $code): QueryHandlerInterface
    {
        $definition = $this->definitions->get($code);
        $factory = $this->factories()[$definition->provider] ?? throw new UnknownProviderException($definition->provider, array_keys($this->factories()));
        $strategy = null;
        if (!\in_array($definition->count, [ListDefinition::COUNT_AUTO, ListDefinition::COUNT_NONE], true)) {
            $strategy = $this->countStrategies->get($definition->count);
            \assert($strategy instanceof CountStrategyInterface);
        }

        return $factory->createQueryHandler($definition, $this->filterTypes, $strategy);
    }

    /**
     * @return array<string, QueryHandlerFactoryInterface>
     */
    private function factories(): array
    {
        if (null === $this->factories) {
            $factories = [];
            foreach ($this->factorySource as $factory) {
                $factories[$factory::getProvider()] = $factory;
            }
            $this->factories = $factories;
        }

        return $this->factories;
    }
}
