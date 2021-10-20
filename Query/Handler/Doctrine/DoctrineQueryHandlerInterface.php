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

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Adding Doctrine logic on top of base query handler logic
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DoctrineQueryHandlerInterface extends QueryHandlerInterface
{
    public function getAlias(): string;

    public function getEntityReference(): string;

    public function getQueryBuilder(): QueryBuilder;

    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias);

    public function resolveAttributeAlias(string $attributePath): string;

    public function getAttributeMetadata(string $attributePath): array;
}
