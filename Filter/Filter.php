<?php

namespace Sidus\FilterBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use Symfony\Component\Form\FormInterface;

class Filter implements FilterInterface
{
    /** @var string */
    protected $code;

    /** @var array */
    protected $attributes;

    /** @var FilterTypeInterface */
    protected $filterType;

    /** @var string */
    protected $label;

    /** @var array */
    protected $options;

    /** @var array */
    protected $formOptions = [];

    /**
     * @param string $code
     * @param FilterTypeInterface $filterType
     * @param array $options
     * @param array|null $attributes
     */
    public function __construct($code, FilterTypeInterface $filterType, array $options = null, array $attributes = null)
    {
        $this->code = $code;
        $this->filterType = $filterType;
        $this->options = $options;
        $this->attributes = empty($attributes) ? [$code] : $attributes;
    }

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
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Filter
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @return array
     */
    public function getFormOptions(QueryBuilder $qb, $alias)
    {
        $defaultOptions = [
            'label' => $this->getLabel(),
            'required' => false,
        ];
        $typeOptions = $this->getFilterType()->getFormOptions($this, $qb, $alias);
        return array_merge($defaultOptions, $typeOptions, $this->formOptions);
    }

    /**
     * @param array $formOptions
     * @return Filter
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
        return $this;
    }

    /**
     * @param FormInterface $form
     * @param QueryBuilder $qb
     * @param string $alias
     */
    public function handleForm(FormInterface $form, QueryBuilder $qb, $alias)
    {
        $this->getFilterType()->handleForm($this, $form, $qb, $alias);
    }

    /**
     * @param string $alias
     * @return array
     */
    public function getFullAttributeReferences($alias)
    {
        $references = [];
        foreach ($this->getAttributes() as $attribute) {
            if (false === strpos($attribute, '.')) {
                $references[] = $alias . '.' . $attribute;
            } else {
                $references[] = $attribute;
            }
        }
        return $references;
    }
}
