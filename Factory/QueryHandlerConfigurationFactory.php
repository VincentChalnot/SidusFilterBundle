<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfiguration;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use UnexpectedValueException;

/**
 * Converts an array configuration into QueryHandlerConfiguration objects
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class QueryHandlerConfigurationFactory implements QueryHandlerConfigurationFactoryInterface
{
    /** @var FilterFactoryInterface */
    protected $filterFactory;

    /**
     * @param FilterFactoryInterface $filterFactory
     */
    public function __construct(FilterFactoryInterface $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws UnexpectedValueException
     * @throws ExceptionInterface
     *
     * @return QueryHandlerConfigurationInterface
     */
    public function createQueryHandlerConfiguration(
        string $code,
        array $configuration
    ): QueryHandlerConfigurationInterface {
        /** @var array[] $filters */
        $filters = $configuration['filters'];
        unset($configuration['filters']);

        $queryHandlerConfiguration = new QueryHandlerConfiguration(
            $code,
            $configuration
        );

        foreach ($filters as $filterCode => $filterConfiguration) {
            $queryHandlerConfiguration->addFilter(
                $this->filterFactory->createFilter($queryHandlerConfiguration, $filterCode, $filterConfiguration)
            );
        }

        return $queryHandlerConfiguration;
    }
}
