<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

class TextFilterType extends FilterType
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
            $uid = uniqid('text', true);
            $dql[] = "{$column} LIKE :{$uid}";
            $qb->setParameter($uid, '%' . $data . '%');
        }
        $qb->andWhere(implode(' OR ', $dql));
    }
}
