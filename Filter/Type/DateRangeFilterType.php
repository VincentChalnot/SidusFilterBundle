<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormInterface;

class DateRangeFilterType extends FilterType
{
    /**
     * @param FilterInterface $filter
     * @param FormInterface $form
     * @param QueryBuilder $qb
     * @param string $alias
     */
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
                $uid = uniqid('fromDate',  true);
                $dql[] = "{$column} >= :{$uid}";
                $qb->setParameter($uid, $startDate);
            }
            $qb->andWhere(implode(' OR ', $dql));
        }
        if (!empty($data[DateRangeType::END_NAME])) {
            $endDate = $data[DateRangeType::END_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid('endDate', true);
                $dql[] = "{$column} <= :{$uid}";
                $qb->setParameter($uid, $endDate);
            }
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
