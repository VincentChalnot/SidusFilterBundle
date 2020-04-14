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

use Sidus\FilterBundle\Filter\Filter;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use UnexpectedValueException;

/**
 * Factory for doctrine filters
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FilterFactory implements FilterFactoryInterface
{
    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     * @param string                             $code
     * @param array                              $configuration
     *
     * @throws UnexpectedValueException
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
            $configuration['form_options'],
            $configuration['default']
        );
    }
}
