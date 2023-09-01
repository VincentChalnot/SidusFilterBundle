<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Builds Query Handlers based on their configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
#[AutoconfigureTag('sidus.query_handler_factory')]
interface QueryHandlerFactoryInterface
{
    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface;

    public static function getProvider(): string;
}
