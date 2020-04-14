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

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Base logic for all filter factories
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
