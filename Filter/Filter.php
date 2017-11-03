<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

/**
 * Base filter logic
 */
class Filter implements FilterInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $provider;

    /** @var FilterTypeInterface */
    protected $filterType;

    /** @var array */
    protected $attributes = [];

    /** @var string */
    protected $formType;

    /** @var string */
    protected $label;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $formOptions = [];

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return FilterTypeInterface
     */
    public function getFilterType(): FilterTypeInterface
    {
        return $this->filterType;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
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
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormType(): string
    {
        if (null !== $this->formType) {
            return $this->formType;
        }

        return $this->getFilterType()->getFormType();
    }

    /**
     * @param string $formType
     */
    public function setFormType(string $formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
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
