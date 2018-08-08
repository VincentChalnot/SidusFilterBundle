<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Pagination\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
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

    /** @var string */
    protected $entityReference;

    /** @var EntityRepository */
    protected $repository;

    /** @var string */
    protected $alias = 'e';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @param FilterTypeRegistry                 $filterTypeRegistry
     * @param QueryHandlerConfigurationInterface $configuration
     * @param ManagerRegistry                    $doctrine
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(
        FilterTypeRegistry $filterTypeRegistry,
        QueryHandlerConfigurationInterface $configuration,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($filterTypeRegistry, $configuration);
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
        $attributesList = explode('.', $attributePath);
        $previousAttribute = $this->getAlias().'.'.array_shift($attributesList);
        $resolvedAttribute = $previousAttribute;

        // Remaining attributes in attributeList are nested so we need joins
        foreach ($attributesList as $nestedAttribute) {
            $qb = $this->getQueryBuilder();
            $joinAlias = uniqid('nested');
            $qb->join($previousAttribute, $joinAlias);
            $resolvedAttribute = $joinAlias.'.'.$nestedAttribute;
        }

        return $resolvedAttribute;
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
