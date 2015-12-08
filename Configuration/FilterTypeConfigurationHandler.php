<?php

namespace Sidus\FilterBundle\Configuration;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

class FilterTypeConfigurationHandler
{
    /** @var FilterTypeInterface[] */
    protected $filterTypes;

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
    public function getFilterTypes()
    {
        return $this->filterTypes;
    }

    /**
     * @param $code
     * @return FilterTypeInterface
     */
    public function getFilterType($code)
    {
        if (empty($this->filterTypes[$code])) {
            throw new \UnexpectedValueException("No type with code : {$code}");
        }
        return $this->filterTypes[$code];
    }
}