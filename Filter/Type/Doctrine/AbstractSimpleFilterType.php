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

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Base type for simple filters
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractSimpleFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        // Check that the query handler is of the proper type
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }
        if ($this->isEmpty($data)) {
            return;
        }

        $qb = $queryHandler->getQueryBuilder();
        $dql = []; // Prepare an array of DQL statements

        // Fetch all attributes references (all filters must support multiple attributes)
        foreach ($this->getFullAttributeReferences($filter, $queryHandler) as $column) {
            $dql[] = $this->applyDQL($qb, $column, $data);
        }

        // If the array of DQL statements is not empty (it shouldn't), apply it on the query builder with a OR
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }

    /**
     * Must return the DQL statement and set the proper parameters in the QueryBuilder
     *
     * @param QueryBuilder $qb
     * @param string       $column
     * @param mixed        $data
     *
     * @return string
     */
    abstract protected function applyDQL(QueryBuilder $qb, string $column, $data): string;

    /**
     * @param mixed $data
     *
     * @return bool
     */
    protected function isEmpty($data): bool
    {
        return null === $data
            || (is_array($data) && 0 === count($data))
            || ($data instanceof \Countable && $data->count() === 0);
    }

    /**
     * Apply a filter to a column, handling specific requirements
     */
    protected function applyStringOperator(QueryBuilder $qb, string $column, string $uid, string $operator): string
    {
        if ($this->requiresCaseInsensitiveLikeTransform($qb, $operator)) {
            $function = $qb->getEntityManager()->getConfiguration()->getCustomStringFunction('ilike');
            if ($function) {
                // Custom ilike function is supported, let's use it
                if (strtoupper($operator) === 'LIKE') {
                    return "ILIKE({$column}, :{$uid}) = TRUE";
                }
                if (strtoupper($operator) === 'NOT LIKE') {
                    return "ILIKE({$column}, :{$uid}) = FALSE";
                }
            }

            return "LOWER({$column}) {$operator} LOWER(:{$uid})";
        }

        return "{$column} {$operator} :{$uid}";
    }

    /**
     * Some platforms behave differently regarding case-sensitiveness, for example a LIKE is usually case-insensitive in
     * MySQL (it might depend on the collation) but is case-sensitive in PostgreSQL
     */
    protected function requiresCaseInsensitiveLikeTransform(QueryBuilder $qb, string $operator): bool
    {
        if (!in_array(strtoupper($operator), ['LIKE', 'NOT LIKE'])) {
            return false;
        }
        $platform = $qb->getEntityManager()->getConnection()->getDatabasePlatform();
        if (null === $platform) {
            return false;
        }

        return 'postgresql' === $platform->getName();
    }
}
