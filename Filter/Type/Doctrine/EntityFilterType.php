<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\EntityRepository;
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

        if (isset($filter->getFormOptions()['choices'])) {
            return parent::getFormOptions($queryHandler, $filter);
        }

        if (\count($filter->getAttributes()) !== 1) {
            throw new \LogicException("Multiple attributes for 'entity' filter type are not supported");
        }
        $attributes = $filter->getAttributes();
        $attributePath = reset($attributes);

        $metadata = $queryHandler->getAttributeMetadata($attributePath);

        // Checking if attribute is a relation or a scalar
        if (!isset($metadata['targetEntity'])) {
            $m = "Attribute path {$attributePath} resolve to a scalar attribute, use the 'choice' filter ";
            $m .= "type instead of the 'entity'";
            throw new \LogicException($m);
        }

        $originalQb = $queryHandler->getQueryBuilder();
        $column = $queryHandler->resolveAttributeAlias($attributePath);

        $qb = clone $queryHandler->getQueryBuilder();
        $qb->select("IDENTITY({$column})")
            ->groupBy($column);

        $queryHandler->setQueryBuilder($originalQb, $queryHandler->getAlias());

        return array_merge(
            $this->formOptions,
            [
                'class' => $metadata['targetEntity'],
                'em' => $queryHandler->getQueryBuilder()->getEntityManager(),
                'query_builder' => static function (EntityRepository $repository) use ($qb) {
                    $targetQb = $repository->createQueryBuilder('t');
                    $targetQb
                        ->where("t.id IN ({$qb->getDQL()})")
                        ->setParameters($qb->getParameters());

                    return $targetQb;
                },
            ],
            $filter->getFormOptions()
        );
    }
}
