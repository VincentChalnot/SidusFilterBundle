<?php

namespace Sidus\FilterBundle\Registry;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

/**
 * Registry for filter types
 */
class FilterTypeRegistry
{
    /** @var FilterTypeInterface[] */
    protected $filterTypes = [];

    /**
     * @param FilterTypeInterface $filterType
     */
    public function addFilterType(FilterTypeInterface $filterType)
    {
        $this->filterTypes[$filterType->getName()] = $filterType;
    }

    /**
     * @return FilterTypeInterface[]
     */
    public function getFilterTypes(): array
    {
        return $this->filterTypes;
    }

    /**
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
     * @return FilterTypeInterface
     */
    public function getFilterType($code): FilterTypeInterface
    {
        if (empty($this->filterTypes[$code])) {
            throw new \UnexpectedValueException("No type with code : {$code}");
        }

        return $this->filterTypes[$code];
    }
}
