<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Base logic common to all filter systems
 */
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
     */
    public function setFormType($formType);

    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions);

    /**
     * @return array
     */
    public function getFormOptions();
}
