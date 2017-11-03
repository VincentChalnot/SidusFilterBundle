<?php

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Adding Doctrine logic on top of base query handler logic
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DoctrineQueryHandlerInterface extends QueryHandlerInterface
{
    /**
     * @return string
     */
    public function getAlias(): string;

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias);
}
