<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Filter logic for choice with Doctrine entities
 */
class ChoiceFilterType extends AbstractDoctrineFilterType
{
    /**
     * @param FilterInterface $filter
     * @param FormInterface   $form
     * @param QueryBuilder    $qb
     * @param string          $alias
     *
     * @throws \LogicException
     */
    public function handleForm(FilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias)
    {
        $data = $form->getData();
        if (null === $data || !$form->isSubmitted()) {
            return;
        }
        if (is_array($data) && 0 === count($data)) {
            return;
        }
        $dql = [];
        foreach ($this->getFullAttributeReferences($filter, $alias) as $column) {
            $uid = uniqid('choices', false);
            if (is_array($data)) {
                $dql[] = "{$column} IN (:{$uid})";
                $qb->setParameter($uid, $data);
            } else {
                $dql[] = "{$column} = :{$uid}";
                $qb->setParameter($uid, $data);
            }
        }
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function getFormOptions(FilterInterface $filter): array
    {
        if (isset($this->formOptions['choices'])) {
            return $this->formOptions;
        }
        $choices = [];
//        $alias = 'e';
//        foreach ($this->getFullAttributeReferences($filter, $alias) as $column) {
//            $qb = clone $qb;
//            $qb->select("{$column} AS __value")
//                ->groupBy($column);
//            foreach ($qb->getQuery()->getArrayResult() as $result) {
//                $value = $result['__value'];
//                $choices[$value] = $value;
//            }
//        }

        return array_merge($this->formOptions, ['choices' => $choices]);
    }
}
