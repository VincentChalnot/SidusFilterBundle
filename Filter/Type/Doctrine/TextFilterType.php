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

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Simple text filtering with Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class TextFilterType extends AbstractDoctrineFilterType
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

        $qb = $queryHandler->getQueryBuilder();
        $dql = []; // Prepare an array of DQL statements

        // Fetch all attributes references (all filters must support multiple attributes)
        foreach ($this->getFullAttributeReferences($filter, $queryHandler) as $column) {
            $uid = uniqid('text'); // Generate random parameter names to prevent collisions
            $dql[] = "{$column} LIKE :{$uid}"; // Add the dql statement to the list
            $qb->setParameter($uid, '%'.$data.'%'); // Add the parameter
        }

        // If the array of DQL statements is not empty (it shouldn't), apply it on the query builder with a OR
        if (0 < \count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
