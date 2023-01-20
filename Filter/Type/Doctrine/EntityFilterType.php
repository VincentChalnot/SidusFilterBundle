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

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Filter logic for choice with Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EntityFilterType extends ChoiceFilterType
{
    /**
     * {@inheritdoc}
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }

        if (\count($filter->getAttributes()) !== 1) {
            throw new \LogicException("Multiple attributes for 'entity' filter type are not supported");
        }
        $attributes = $filter->getAttributes();
        $attributePath = reset($attributes);

        $metadata = $queryHandler->getAttributeMetadata($attributePath);

        $qb = $this->getQueryBuilder($queryHandler, $metadata, $attributePath);

        return array_merge(
            $this->formOptions,
            [
                'class' => $metadata['targetEntity'],
                'em' => $queryHandler->getQueryBuilder()->getEntityManager(),
                'query_builder' => static function (EntityRepository $repository) use ($qb) {
                    $classMetadata = $qb->getEntityManager()->getClassMetadata($repository->getClassName());
                    $targetQb = $repository->createQueryBuilder('t');
                    $targetQb
                        ->where("t.{$classMetadata->getSingleIdentifierFieldName()} IN ({$qb->getDQL()})")
                        ->setParameters($qb->getParameters());

                    return $targetQb;
                },
            ],
            $filter->getFormOptions()
        );
    }

    protected function getQueryBuilder(DoctrineQueryHandlerInterface $queryHandler, array $metadata, $attributePath): QueryBuilder
    {
        // Checking if attribute is a relation or a scalar
        if (!isset($metadata['targetEntity'])) {
            $m = "Attribute path {$attributePath} resolve to a scalar attribute, use the 'choice' filter ";
            $m .= "type instead of the 'entity'";
            throw new \LogicException($m);
        }

        $originalQb = $queryHandler->getQueryBuilder();
        $column = $queryHandler->resolveAttributeAlias($attributePath);

        $qb = clone $queryHandler->getQueryBuilder();
        if ($metadata['id'] ?? false) { // This is a good way to know if we are in a *toMany relation
            // Specific case for *ToMany relations as the id is not available through a local column
            $qb->select($column);
        } else {
            $qb->select("IDENTITY({$column})");
        }
        $qb->groupBy($column);

        $queryHandler->setQueryBuilder($originalQb, $queryHandler->getAlias());

        return $qb;
    }
}
