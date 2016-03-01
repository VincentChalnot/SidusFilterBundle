<?php

namespace Sidus\FilterBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormTypeInterface;

abstract class FilterType implements FilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var FormTypeInterface|string */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /**
     * @param string $name
     * @param FormTypeInterface $formType
     * @param array $formOptions
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
     * @inheritDoc
     */
    public function getFormOptions(FilterInterface $filter, QueryBuilder $qb, $alias)
    {
        return $this->formOptions;
    }
}
