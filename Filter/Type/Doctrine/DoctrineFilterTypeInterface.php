<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterInterface;
use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Specific logic for Doctrine filter types
 */
interface DoctrineFilterTypeInterface extends FilterTypeInterface
{
    /**
     * @param DoctrineFilterInterface $filter
     * @param FormInterface           $form
     * @param QueryBuilder            $qb
     * @param string                  $alias
     */
    public function handleForm(DoctrineFilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias);

    /**
     * @param DoctrineFilterInterface $filter
     * @param QueryBuilder            $qb
     * @param string                  $alias
     *
     * @return array
     */
    public function getDoctrineFormOptions(DoctrineFilterInterface $filter, QueryBuilder $qb, $alias);
}
