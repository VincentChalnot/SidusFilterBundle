<?php

namespace Sidus\FilterBundle\Filter\Type;

use Symfony\Component\Form\FormTypeInterface;

abstract class FilterType implements FilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var FormTypeInterface|string */
    protected $formType;

    /**
     * @param $name
     * @param FormTypeInterface $formType
     */
    public function __construct($name, $formType)
    {
        $this->name = $name;
        $this->formType = $formType;
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
}
