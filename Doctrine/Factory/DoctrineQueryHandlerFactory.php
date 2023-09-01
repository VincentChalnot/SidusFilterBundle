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

namespace Sidus\FilterBundle\Doctrine\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Sidus\FilterBundle\Doctrine\Metadata\DoctrineAttributeMetadataResolver;
use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandler;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use UnexpectedValueException;

/**
 * Dedicated logic for Doctrine query handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineQueryHandlerFactory implements QueryHandlerFactoryInterface
{
    public function __construct(
        protected FilterTypeRegistry $filterTypeRegistry,
        protected ManagerRegistry $doctrine,
        protected DoctrineAttributeMetadataResolver $doctrineAttributeMetadataResolver,
    ) {
    }

    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface {
        return new DoctrineQueryHandler(
            $this->filterTypeRegistry,
            $queryHandlerConfiguration,
            $this->doctrine,
            $this->doctrineAttributeMetadataResolver
        );
    }

    public static function getProvider(): string
    {
        return 'doctrine';
    }
}
