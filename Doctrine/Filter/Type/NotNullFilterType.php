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

namespace Sidus\FilterBundle\Doctrine\Filter\Type;

use Doctrine\ORM\QueryBuilder;

/**
 * Simple test to check if column has values
 */
class NotNullFilterType extends AbstractSimpleFilterType
{
    protected function applyDQL(QueryBuilder $qb, string $column, $data): string
    {
        return "{$column} IS NOT NULL";
    }

    protected function isEmpty(mixed $data): bool
    {
        return empty($data);
    }
}
