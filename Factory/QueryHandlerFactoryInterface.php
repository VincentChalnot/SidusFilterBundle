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
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Builds Query Handlers based on their configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
