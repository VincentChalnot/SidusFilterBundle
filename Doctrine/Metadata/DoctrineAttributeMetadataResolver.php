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

namespace Sidus\FilterBundle\Doctrine\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineAttributeMetadataResolver
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function getAttributeMetadata(
        string $class,
        string $rootAlias,
        string $attributePath,
        ?QueryBuilder $qb,
        string $joinType = Join::LEFT_JOIN,
    ): array {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException("No manager for class {$class}");
        }
        $entityMetadata = $entityManager->getClassMetadata($class);

        $attributesList = explode('.', $attributePath);

        $previousAttributeIsScalar = false;
        $attributeMetadata = null;
        $previousAlias = $rootAlias;
        $embeddedRelation = null;
        foreach ($attributesList as $nestedAttribute) {
            if ($previousAttributeIsScalar) {
                $m = "Can't resolve path {$attributePath}, trying to resolve a relation on a scalar attribute.";
                throw new \UnexpectedValueException($m);
            }
            if (null !== $embeddedRelation) {
                $nestedAttribute = $embeddedRelation.'.'.$nestedAttribute;
                $embeddedRelation = null;
            }
            if ($entityMetadata->hasAssociation($nestedAttribute)) {
                $previousAttributeMetadata = $attributeMetadata;
                $attributeMetadata = $entityMetadata->getAssociationMapping($nestedAttribute);
                $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy

                $nestedEntityReference = $entityMetadata->getAssociationTargetClass($nestedAttribute);
                $entityMetadata = $entityManager->getClassMetadata($nestedEntityReference);

                if ($qb) {
                    $attributeMetadata['alias'] = "{$previousAlias}.{$nestedAttribute}";
                    $joinAlias = uniqid('nested'.ucfirst($nestedAttribute), false);
                    if (Join::INNER_JOIN === $joinType) {
                        $qb->innerJoin($attributeMetadata['alias'], $joinAlias);
                    } elseif (Join::LEFT_JOIN === $joinType) {
                        $qb->leftJoin($attributeMetadata['alias'], $joinAlias);
                    } else {
                        throw new \UnexpectedValueException("Unknown join type {$joinType}");
                    }
                    $previousAlias = $joinAlias;
                }
            } elseif ($entityMetadata->hasField($nestedAttribute)) {
                $previousAttributeMetadata = $attributeMetadata;
                if (isset($entityMetadata->embeddedClasses[$nestedAttribute])) {
                    $embeddedRelation = $nestedAttribute;
                    continue;
                }
                $previousAttributeIsScalar = true;
                $attributeMetadata = $entityMetadata->getFieldMapping($nestedAttribute);
                $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy

                if ($qb) {
                    $attributeMetadata['alias'] = "{$previousAlias}.{$nestedAttribute}";
                }
            } else {
                $m = "Unknown attribute {$nestedAttribute} in class {$entityMetadata->getName()}.";
                $m .= " Path: {$attributePath}";
                throw new \UnexpectedValueException($m);
            }
        }

        if (null === $attributeMetadata) {
            throw new \LogicException("Unable to resolve attribute path {$attributePath}, no metadata found");
        }

        // *ToMany relations do not behave like other associations, we must join on the relation once more to point to
        // the id because we can't use IDENTITY() on them
        $toManyTypes = [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY];
        if (in_array($attributeMetadata['type'], $toManyTypes, true)) {
            $entityMetadata = $entityManager->getClassMetadata($attributeMetadata['targetEntity']);
            $previousAttributeMetadata = $attributeMetadata;
            $attributeMetadata = $entityMetadata->getFieldMapping($entityMetadata->getSingleIdentifierFieldName());
            $attributeMetadata['parent'] = $previousAttributeMetadata; // Keep the metadata hierarchy
            // Also pass targetEntity to mimic a relationship behavior
            $attributeMetadata['targetEntity'] = $entityMetadata->getName();
            if ($qb) {
                // Alias was already applied, this is what makes *ToMany weird
                $attributeMetadata['alias'] = "{$previousAlias}.{$attributeMetadata['fieldName']}";
            }
        }

        return $attributeMetadata;
    }
}
