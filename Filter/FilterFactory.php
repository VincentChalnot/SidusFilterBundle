<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Configuration\FilterTypeConfigurationHandler;

class FilterFactory
{
    /** @var string */
    protected $filterClass;

    /** @var FilterTypeConfigurationHandler */
    protected $filterTypeConfigurationHandler;

    /**
     * @param string $filterClass
     * @param FilterTypeConfigurationHandler $filterTypeConfigurationHandler
     */
    public function __construct($filterClass, FilterTypeConfigurationHandler $filterTypeConfigurationHandler)
    {
        $this->filterClass = $filterClass;
        $this->filterTypeConfigurationHandler = $filterTypeConfigurationHandler;
    }

    /**
     * @param string $code
     * @param array $configuration
     * @return FilterInterface
     */
    public function create($code, array $configuration)
    {
        $filterType = $this->filterTypeConfigurationHandler->getFilterType($configuration['type']);
        $options = empty($configuration['options']) ? [] : $configuration['options'];
        $attributes = empty($configuration['attributes']) ? [] : $configuration['attributes'];
        /** @var FilterInterface $filter */
        $filter = new $this->filterClass($code, $filterType, $options, $attributes);
        if (!empty($configuration['label'])) {
            $filter->setLabel($configuration['label']);
        }
        if (!empty($configuration['form_options'])) {
            $filter->setFormOptions((array) $configuration['form_options']);
        }
        return $filter;
    }
}