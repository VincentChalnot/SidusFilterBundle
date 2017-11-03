<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Filter\FilterInterface;

/**
 * Factory for doctrine filters
 */
class FilterFactory implements FilterFactoryInterface
{
    /** @var string */
    protected $provider;

    /** @var string */
    protected $filterClass;

    /** @var FilterTypeRegistry */
    protected $filterTypeConfigurationHandler;

    /**
     * @param string             $filterClass
     * @param FilterTypeRegistry $filterTypeConfigurationHandler
     */
    public function __construct($filterClass, FilterTypeRegistry $filterTypeConfigurationHandler)
    {
        $this->filterClass = $filterClass;
        $this->filterTypeConfigurationHandler = $filterTypeConfigurationHandler;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws \UnexpectedValueException
     *
     * @return FilterInterface
     */
    public function createFilter(string $code, array $configuration) : FilterInterface
    {
        $filterType = $this->filterTypeConfigurationHandler->getFilterType($configuration['type']);
        $options = empty($configuration['options']) ? [] : $configuration['options'];
        $attributes = empty($configuration['attributes']) ? [] : $configuration['attributes'];
        $filterClass = $this->filterClass;
        /** @var FilterInterface $filter */
        $filter = new $filterClass($code, $filterType, $options, $attributes);
        if (!empty($configuration['label'])) {
            $filter->setLabel($configuration['label']);
        }
        if (!empty($configuration['form_options'])) {
            $filter->setFormOptions((array) $configuration['form_options']);
        }
        if (!empty($configuration['form_type'])) {
            $filter->setFormType($configuration['form_type']);
        }

        return $filter;
    }
}
