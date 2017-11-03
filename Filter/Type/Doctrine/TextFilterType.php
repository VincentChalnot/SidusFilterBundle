<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Simple text filtering with Doctrine entities
 */
class TextFilterType extends AbstractDoctrineFilterType
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
        $dql = [];
        foreach ($this->getFullAttributeReferences($filter, $alias) as $column) {
            $uid = uniqid('text', false);
            $dql[] = "{$column} LIKE :{$uid}";
            $qb->setParameter($uid, '%'.$data.'%');
        }
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
