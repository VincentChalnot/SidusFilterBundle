<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Container service (immutable, shared) that creates one query handler per request. Indexed by provider name.
 * The static methods are called at compile time without instantiating the service.
 */
interface QueryHandlerFactoryInterface
{
    public static function getProvider(): string;

    /** Validates the list's `options` (e.g. `entity`, `source`). */
    public static function configureListOptions(OptionsResolver $resolver): void;

    /** Validates the filter's `provider_options.<provider>` entry. */
    public static function configureFilterOptions(OptionsResolver $resolver): void;

    /** Must return a new object on every call. */
    public function createQueryHandler(ListDefinition $definition, FilterTypeRegistry $filterTypes, ?CountStrategyInterface $countStrategy): QueryHandlerInterface;
}
