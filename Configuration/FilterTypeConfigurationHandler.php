<?php

namespace Sidus\FilterBundle\Configuration;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

/**
 * Registry for filter types
 */
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
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
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
