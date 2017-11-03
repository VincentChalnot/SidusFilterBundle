<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Specific logic for Doctrine filter types
 */
interface DoctrineFilterTypeInterface extends FilterTypeInterface
{
    /**
     * @param FilterInterface $filter
     * @param FormInterface   $form
     * @param QueryBuilder    $qb
     * @param string          $alias
     */
    public function handleForm(FilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias);
}
