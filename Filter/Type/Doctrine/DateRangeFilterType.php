<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormInterface;

/**
 * Filtering on dates with Doctrine entities
 */
class DateRangeFilterType extends AbstractDoctrineFilterType
{
    /**
     * @param DoctrineFilterInterface $filter
     * @param FormInterface           $form
     * @param QueryBuilder            $qb
     * @param string                  $alias
     */
    public function handleForm(DoctrineFilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias)
    {
        $data = $form->getData();
        if (null === $data || !$form->isSubmitted()) {
            return;
        }
        $columns = $filter->getFullAttributeReferences($alias);
        if (!empty($data[DateRangeType::START_NAME])) {
            $startDate = $data[DateRangeType::START_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid('fromDate', false);
                $dql[] = "{$column} >= :{$uid}";
                $qb->setParameter($uid, $startDate);
            }
            if (0 < count($dql)) {
                $qb->andWhere(implode(' OR ', $dql));
            }
        }
        if (!empty($data[DateRangeType::END_NAME])) {
            $endDate = $data[DateRangeType::END_NAME];
            $dql = [];
            foreach ($columns as $column) {
                $uid = uniqid('endDate', false);
                $dql[] = "{$column} <= :{$uid}";
                $qb->setParameter($uid, $endDate);
            }
            if (0 < count($dql)) {
                $qb->andWhere(implode(' OR ', $dql));
            }
        }
    }
}
