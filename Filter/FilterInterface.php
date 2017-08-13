<?php

namespace Sidus\FilterBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

interface FilterInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @return FilterTypeInterface
     */
    public function getFilterType();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param string $label
     *
     * @return FilterInterface
     */
    public function setLabel($label);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * Override form type from default filter type
     *
     * @return FormTypeInterface|string
     */
    public function getFormType();

    /**
     * @param string $formType
     *
     * @return Filter
     */
    public function setFormType($formType);

    /**
     * @param QueryBuilder $qb
     * @param              $alias
     *
     * @return array
     */
    public function getFormOptions(QueryBuilder $qb, $alias);

    /**
     * @param array $formOptions
     *
     * @return FilterInterface
     */
    public function setFormOptions(array $formOptions);

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
