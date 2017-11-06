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
        $this->filterTypes[$filterType->getProvider()][$filterType->getName()] = $filterType;
    }

    /**
     * @return FilterTypeInterface[]
     */
    public function getFilterTypes(): array
    {
        return $this->filterTypes;
    }

    /**
     * @param string $provider
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
     * @return FilterTypeInterface
     */
    public function getFilterType(string $provider, string $code): FilterTypeInterface
    {
        if (empty($this->filterTypes[$provider][$code])) {
            throw new \UnexpectedValueException("No type for provider {$provider} with code : {$code}");
        }

        return $this->filterTypes[$provider][$code];
    }
}
