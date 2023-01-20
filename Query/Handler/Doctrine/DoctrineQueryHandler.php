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

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\Doctrine\DoctrineAttributeMetadataResolver;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Pagination\DoctrineORMAdapter;
use Sidus\FilterBundle\Query\Handler\AbstractQueryHandler;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use UnexpectedValueException;

/**
 * Build the necessary logic around filters based on a configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineQueryHandler extends AbstractQueryHandler implements DoctrineQueryHandlerInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var DoctrineAttributeMetadataResolver */
    protected $doctrineAttributeMetadataResolver;

    /** @var string */
    protected $entityReference;

    /** @var EntityRepository */
    protected $repository;

    /** @var string */
    protected $alias = 'e';

    /** @var QueryBuilder */
    protected $queryBuilder;

    public function __construct(
        FilterTypeRegistry $filterTypeRegistry,
        QueryHandlerConfigurationInterface $configuration,
        ManagerRegistry $doctrine,
        DoctrineAttributeMetadataResolver $doctrineAttributeMetadataResolver
    ) {
        parent::__construct($filterTypeRegistry, $configuration);
        $this->doctrineAttributeMetadataResolver = $doctrineAttributeMetadataResolver;
        $this->entityReference = $configuration->getOption('entity');
        if (null === $this->entityReference) {
            throw new UnexpectedValueException(
                "Missing 'entity' option for filter configuration {$configuration->getCode()}"
            );
        }
        $this->entityManager = $doctrine->getManagerForClass($this->entityReference);
        if (!$this->entityManager) {
            throw new UnexpectedValueException("No manager found for class {$this->entityReference}");
        }
        $this->repository = $this->entityManager->getRepository($this->entityReference);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getEntityReference(): string
    {
        return $this->entityReference;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = $this->repository->createQueryBuilder($this->alias);
        }

        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias)
    {
        $this->alias = $alias;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param string $attributePath
     *
     * @return string
     */
    public function resolveAttributeAlias(string $attributePath): string
    {
        $metadata = $this->getAttributeMetadata($attributePath, Join::LEFT_JOIN);

        return $metadata['alias'];
    }

    /**
     * If $applyJoin is set to true, necessary joins will be applied to the query builder and the attribute alias will
     * be returned in the "alias" key of the result.
     */
    public function getAttributeMetadata(string $attributePath, ?string $joinType = null): array
    {
        return $this->doctrineAttributeMetadataResolver->getAttributeMetadata(
            $this->entityReference,
            $this->getAlias(),
            $attributePath,
            null === $joinType ? null : $this->getQueryBuilder(),
            $joinType ?? Join::LEFT_JOIN
        );
    }

    /**
     * @param SortConfig $sortConfig
     */
    protected function applySort(SortConfig $sortConfig)
    {
        $column = $sortConfig->getColumn();
        if ($column) {
            $direction = $sortConfig->getDirection() ? 'DESC' : 'ASC'; // null or false both default to ASC
            $this->getQueryBuilder()->addOrderBy($this->resolveAttributeAlias($column), $direction);
        }
    }

    /**
     * @return Pagerfanta
     */
    protected function createPager(): Pagerfanta
    {
        return new Pagerfanta(new DoctrineORMAdapter($this->getQueryBuilder()));
    }
}
