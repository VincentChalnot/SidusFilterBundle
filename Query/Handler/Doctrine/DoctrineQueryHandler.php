<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Pagerfanta;
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
     * @throws UnexpectedValueException
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
        $metadata = $this->getAttributeMetadata($attributePath, true);

        return $metadata['alias'];
    }

    /**
     * If $applyJoin is set to true, necessary joins will be applied to the query builder and the attribute alias will
     * be returned in the "alias" key of the result.
     */
    public function getAttributeMetadata(string $attributePath, bool $applyJoin = false): array
    {
        $entityMetadata = $this->entityManager->getClassMetadata($this->entityReference);

        $attributesList = explode('.', $attributePath);

        $previousAttributeIsScalar = false;
        $attributeMetadata = null;
        $previousAlias = $this->getAlias();
        foreach ($attributesList as $nestedAttribute) {
            if ($previousAttributeIsScalar) {
                $m = "Can't resolve path {$attributePath}, trying to resolve a relation on a scalar attribute.";
                throw new UnexpectedValueException($m);
            }
            if ($entityMetadata->hasAssociation($nestedAttribute)) {
                $previousAttributeMetadata = $attributeMetadata;
                $attributeMetadata = $entityMetadata->getAssociationMapping($nestedAttribute);
                $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy

                $nestedEntityReference = $entityMetadata->getAssociationTargetClass($nestedAttribute);
                $entityMetadata = $this->entityManager->getClassMetadata($nestedEntityReference);

                if ($applyJoin) {
                    $attributeMetadata['alias'] = "{$previousAlias}.{$nestedAttribute}";
                    $qb = $this->getQueryBuilder();
                    $joinAlias = uniqid('nested'.ucfirst($nestedAttribute), false);
                    $qb->leftJoin($attributeMetadata['alias'], $joinAlias);
                    $previousAlias = $joinAlias;
                }
            } elseif ($entityMetadata->hasField($nestedAttribute)) {
                $previousAttributeIsScalar = true;
                $previousAttributeMetadata = $attributeMetadata;
                $attributeMetadata = $entityMetadata->getFieldMapping($nestedAttribute);
                $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy

                if ($applyJoin) {
                    $attributeMetadata['alias'] = "{$previousAlias}.{$nestedAttribute}";
                }
            } else {
                $m = "Unknown attribute {$nestedAttribute} in class {$entityMetadata->getName()}.";
                $m .= " Path: {$attributePath}";
                throw new UnexpectedValueException($m);
            }
        }

        if (null === $attributeMetadata) {
            throw new \LogicException("Unable to resolve attribute path {$attributePath}, no metadata found");
        }

        // *ToMany relations do not behave like other associations, we must join on the relation once more to point to
        // the id because we can't use IDENTITY() on them
        $toManyTypes = [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY];
        if (in_array($attributeMetadata['type'], $toManyTypes, true)) {
            $entityMetadata = $this->entityManager->getClassMetadata($attributeMetadata['targetEntity']);
            $previousAttributeMetadata = $attributeMetadata;
            $attributeMetadata = $entityMetadata->getFieldMapping($entityMetadata->getSingleIdentifierFieldName());
            $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy
            // Also pass targetEntity to mimic a relationship behavior
            $attributeMetadata['targetEntity'] = $entityMetadata->getName();
            if ($applyJoin) {
                // Alias was already applied, this is what makes *ToMany weird
                $attributeMetadata['alias'] = "{$previousAlias}.{$attributeMetadata['fieldName']}";
            }
        }

        return $attributeMetadata;
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
