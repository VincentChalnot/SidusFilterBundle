<?php

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Generic filter type
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /**
     * @param string $name
     * @param string $formType
     * @param array  $formOptions
     */
    public function __construct(string $name, string $formType, array $formOptions = [])
    {
        $this->name = $name;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        return array_merge(
            [
                'required' => false,
            ],
            $this->formOptions,
            $filter->getFormOptions()
        );
    }
}
