<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Converts an array configuration into QueryHandlerConfiguration objects
 */
interface QueryHandlerConfigurationFactoryInterface
{
    /**
     * @param string $code
     * @param array  $configuration
     *
     * @return QueryHandlerConfigurationInterface
     */
    public function createQueryHandlerConfiguration(
        string $code,
        array $configuration
    ): QueryHandlerConfigurationInterface;
}
