<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Filter\Filter;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Factory for doctrine filters
 */
class FilterFactory implements FilterFactoryInterface
{
    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     * @param string                             $code
     * @param array                              $configuration
     *
     * @throws \UnexpectedValueException
     *
     * @return FilterInterface
     */
    public function createFilter(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration,
        string $code,
        array $configuration
    ): FilterInterface {
        return new Filter(
            $queryHandlerConfiguration,
            $code,
            $configuration['type'],
            $configuration['attributes'],
            $configuration['form_type'],
            $configuration['label'],
            $configuration['options'],
            $configuration['form_options']
        );
    }
}
