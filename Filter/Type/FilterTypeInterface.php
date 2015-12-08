<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

interface FilterTypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return FormTypeInterface|string
     */
    public function getFormType();

    /**
     * @param FilterInterface $filter
     * @param FormInterface $form
     * @param QueryBuilder $qb
     * @param string $alias
     */
    public function handleForm(FilterInterface $filter, FormInterface $form, QueryBuilder $qb, $alias);
}
