<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Configuration\FilterTypeConfigurationHandler;

class FilterFactory
{
    /** @var string */
    protected $filterClass;

    /** @var FilterTypeConfigurationHandler */
    protected $filterTypeConfigurationhandler;

    /**
     * @param string $filterClass
     * @param FilterTypeConfigurationHandler $filterTypeConfigurationhandler
     */
    public function __construct($filterClass, FilterTypeConfigurationHandler $filterTypeConfigurationhandler)
    {
        $this->filterClass = $filterClass;
        $this->filterTypeConfigurationhandler = $filterTypeConfigurationhandler;
    }

    /**
     * @param string $code
     * @param array $configuration
     * @return FilterInterface
     */
    public function create($code, array $configuration)
    {
        $filterType = $this->filterTypeConfigurationhandler->getFilterType($configuration['type']);
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