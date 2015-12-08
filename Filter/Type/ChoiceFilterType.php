<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

class ChoiceFilterType extends FilterType
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
        $dql = [];
        foreach ($filter->getFullAttributeReferences($alias) as $column) {
            $uid = uniqid();
            if (is_array($data)) {
                $dql[] = "{$column} IN (:choices{$uid})";
                $qb->setParameter('choices' . $uid, $data);
            } else {
                $dql[] = "{$column} = :choice{$uid}";
                $qb->setParameter('choice' . $uid, $data);
            }
        }
        $qb->andWhere(implode(' OR ', $dql));
    }
}
