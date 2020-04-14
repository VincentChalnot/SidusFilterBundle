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

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use function count;
use function is_array;

/**
 * Filtering on dates with Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DateRangeFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }
        if (!is_array($data)) {
            return;
        }

        $startDate = $data[DateRangeType::START_NAME] ?? null;
        $endDate = $data[DateRangeType::END_NAME] ?? null;
        if (null === $startDate && null === $endDate) {
            return;
        }

        $qb = $queryHandler->getQueryBuilder();
        $columns = $this->getFullAttributeReferences($filter, $queryHandler);
        if ($startDate instanceof DateTimeInterface) {
            $this->buildQb($columns, $qb, $startDate, '>=');
        }
        if ($endDate instanceof DateTimeInterface) {
            $this->buildQb($columns, $qb, $endDate, '<=');
        }
    }

    /**
     * @param array        $columns
     * @param QueryBuilder $qb
     * @param DateTime    $value
     * @param string       $operator
     */
    protected function buildQb(array $columns, QueryBuilder $qb, DateTime $value, string $operator): void
    {
        $dql = [];
        foreach ($columns as $column) {
            $uid = uniqid('date', false);
            $dql[] = "{$column} {$operator} :{$uid}";
            $qb->setParameter($uid, $value);
        }
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
