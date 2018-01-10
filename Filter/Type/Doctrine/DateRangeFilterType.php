<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Filtering on dates with Doctrine entities
 */
class DateRangeFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleForm(QueryHandlerInterface $queryHandler, FilterInterface $filter, FormInterface $form)
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }
        $data = $form->getData();
        if (null === $data || (\is_array($data) && 0 === \count($data))) {
            return;
        }

        $qb = $queryHandler->getQueryBuilder();
        $columns = $this->getFullAttributeReferences($filter, $queryHandler->getAlias());
        if (!empty($data[DateRangeType::START_NAME])) {
            $startDate = $data[DateRangeType::START_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid('fromDate');
                $dql[] = "{$column} >= :{$uid}";
                $qb->setParameter($uid, $startDate);
            }
            if (0 < \count($dql)) {
                $qb->andWhere(implode(' OR ', $dql));
            }
        }
        if (!empty($data[DateRangeType::END_NAME])) {
            $endDate = $data[DateRangeType::END_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid('endDate');
                $dql[] = "{$column} <= :{$uid}";
                $qb->setParameter($uid, $endDate);
            }
            if (0 < \count($dql)) {
                $qb->andWhere(implode(' OR ', $dql));
            }
        }
    }
}
