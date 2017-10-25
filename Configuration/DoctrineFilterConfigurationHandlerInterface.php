<?php

namespace Sidus\FilterBundle\Configuration;

use Doctrine\ORM\QueryBuilder;

/**
 * Adding Doctrine logic on top of base filter logic
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DoctrineFilterConfigurationHandlerInterface extends FilterConfigurationHandlerInterface
{
    /**
     * @return string
     */
    public function getAlias();

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder($alias = 'e');

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias);
}
