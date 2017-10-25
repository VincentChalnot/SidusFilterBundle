<?php

namespace Sidus\FilterBundle\Filter\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Doctrine implementation for filters system
 */
interface DoctrineFilterInterface extends FilterInterface
{
    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     *
     * @return array
     */
    public function getDoctrineFormOptions(QueryBuilder $qb, $alias);

    /**
     * @param FormInterface $form
     * @param QueryBuilder  $qb
     * @param string        $alias
     */
    public function handleForm(FormInterface $form, QueryBuilder $qb, $alias);

    /**
     * @param string $alias
     *
     * @return array
     */
    public function getFullAttributeReferences($alias);
}
