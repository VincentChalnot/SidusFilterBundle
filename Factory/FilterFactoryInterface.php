<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Base logic for all filter factories
 */
interface FilterFactoryInterface
{
    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     * @param string                             $code
     * @param array                              $configuration
     *
     * @return FilterInterface
     */
    public function createFilter(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration,
        string $code,
        array $configuration
    ): FilterInterface;
}
