<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use function is_array;

/**
 * Filter logic for choice with Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ChoiceFilterType extends AbstractSimpleFilterType
{
    /**
     * Trying to automatically resolve choice options from database
     *
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

        $choices = [];
        $originalQb = clone $queryHandler->getQueryBuilder(); // Saving current query builder state

        foreach ($this->getFullAttributeReferences($filter, $queryHandler) as $column) {
            $qb = clone $queryHandler->getQueryBuilder();
            $qb->select("{$column} AS __value")
                ->groupBy($column);

            foreach ($qb->getQuery()->getArrayResult() as $result) {
                $value = $result['__value'];
                $choices[$value] = $value;
            }
        }

        // Rolling back to previous query builder to revert automatic joints
        $queryHandler->setQueryBuilder($originalQb, $queryHandler->getAlias());

        return array_merge(
            $this->formOptions,
            $filter->getFormOptions(),
            ['choices' => $choices]
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function applyDQL(QueryBuilder $qb, string $column, $data): string
    {
        $uid = uniqid('choices', false);
        $qb->setParameter($uid, $data);

        if (is_array($data)) {
            return "{$column} IN (:{$uid})";
        }

        return "{$column} = :{$uid}";
    }
}
