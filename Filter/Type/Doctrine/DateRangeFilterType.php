<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormInterface;

/**
 * Filtering on dates with Doctrine entities
 */
class DateRangeFilterType extends AbstractDoctrineFilterType
{
    /**
     * @param FilterInterface $filter
     * @param FormInterface   $form
     * @param QueryBuilder    $qb
     * @param string          $alias
     */
    public function handleForm(FilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias)
    {
        $data = $form->getData();
        if (null === $data || !$form->isSubmitted()) {
            return;
        }
        $columns = $this->getFullAttributeReferences($filter, $alias);
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
