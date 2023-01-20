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

namespace Sidus\FilterBundle\Factory\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Sidus\FilterBundle\Doctrine\DoctrineAttributeMetadataResolver;
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
    /** @var FilterTypeRegistry */
    protected $filterTypeRegistry;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DoctrineAttributeMetadataResolver */
    protected $doctrineAttributeMetadataResolver;

    public function __construct(
        FilterTypeRegistry $filterTypeRegistry,
        ManagerRegistry $doctrine,
        DoctrineAttributeMetadataResolver $doctrineAttributeMetadataResolver
    ) {
        $this->filterTypeRegistry = $filterTypeRegistry;
        $this->doctrine = $doctrine;
        $this->doctrineAttributeMetadataResolver = $doctrineAttributeMetadataResolver;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     *
     * @throws UnexpectedValueException
     *
     * @return QueryHandlerInterface
     */
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

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'doctrine';
    }
}
