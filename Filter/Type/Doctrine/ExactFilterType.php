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

use Doctrine\ORM\QueryBuilder;

/**
 * Simple text filtering with Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ExactFilterType extends AbstractSimpleFilterType
{
    /**
     * {@inheritdoc}
     */
    protected function applyDQL(QueryBuilder $qb, string $column, $data): string
    {
        $uid = uniqid('exact', false); // Generate random parameter names to prevent collisions
        $qb->setParameter($uid, $data); // Add the parameter

        return "{$column} = :{$uid}"; // Add the dql statement to the list
    }
}
