<?php

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Generic filter type
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var FormTypeInterface|string */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /**
     * @param string            $name
     * @param FormTypeInterface $formType
     * @param array             $formOptions
     */
    public function __construct($name, $formType, array $formOptions = [])
    {
        $this->name = $name;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return FormTypeInterface|string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(FilterInterface $filter)
    {
        return $this->formOptions;
    }
}
