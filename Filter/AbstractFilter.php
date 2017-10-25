<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

/**
 * Base filter logic
 */
abstract class AbstractFilter implements FilterInterface
{
    /** @var string */
    protected $code;

    /** @var array */
    protected $attributes;

    /** @var string */
    protected $formType;

    /** @var string */
    protected $label;

    /** @var array */
    protected $options;

    /** @var array */
    protected $formOptions = [];

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes = null)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return FilterTypeInterface
     */
    abstract public function getFilterType();

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormType()
    {
        if (null !== $this->formType) {
            return $this->formType;
        }

        return $this->getFilterType()->getFormType();
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        $defaultOptions = [
            'label' => $this->getLabel(),
            'required' => false,
        ];
        $typeOptions = $this->getFilterType()->getFormOptions($this);

        return array_merge($defaultOptions, $typeOptions, $this->formOptions);
    }

    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
    }
}
