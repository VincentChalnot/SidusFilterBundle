<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Simple text filtering with Doctrine entities
 */
class TextFilterType extends AbstractDoctrineFilterType
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
        $dql = [];
        foreach ($filter->getFullAttributeReferences($alias) as $column) {
            $uid = uniqid('text', false);
            $dql[] = "{$column} LIKE :{$uid}";
            $qb->setParameter($uid, '%'.$data.'%');
        }
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
