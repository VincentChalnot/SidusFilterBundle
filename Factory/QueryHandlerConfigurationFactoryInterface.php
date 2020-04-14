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

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Converts an array configuration into QueryHandlerConfiguration objects
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
