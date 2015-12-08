<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormInterface;

class DateRangeFilterType extends FilterType
{
    public function handleForm(FilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias)
    {
        $data = $form->getData();
        if (!$data) {
            return;
        }
        $columns = $filter->getFullAttributeReferences($alias);
        if (!empty($data[DateRangeType::START_NAME])) {
            $startDate = $data[DateRangeType::START_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid();
                $dql[] = "{$column} >= :fromDate{$uid}";
                $qb->setParameter('fromDate' . $uid, $startDate);
            }
            $qb->andWhere(implode(' OR ', $dql));
        }
        if (!empty($data[DateRangeType::END_NAME])) {
            $endDate = $data[DateRangeType::END_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid();
                $dql[] = "{$column} <= :endDate{$uid}";
                $qb->setParameter('endDate' . $uid, $endDate);
            }
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
