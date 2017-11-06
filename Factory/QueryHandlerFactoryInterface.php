<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Builds Query Handlers based on their configuration
 */
interface QueryHandlerFactoryInterface
{
    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     *
     * @return QueryHandlerInterface
     */
    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface;

    /**
     * @return string
     */
    public function getProvider(): string;
}
