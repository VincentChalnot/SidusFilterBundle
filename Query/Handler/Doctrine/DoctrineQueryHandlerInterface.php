<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @return string
     */
    public function getEntityReference(): string;

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias);

    /**
     * @param string $attributePath
     *
     * @return string
     */
    public function resolveAttributeAlias(string $attributePath): string;

    /**
     * @param string $attributePath
     *
     * @return array
     */
    public function getAttributeMetadata(string $attributePath): array;
}
