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
        /** @var FilterInterface $filter */
        $filter = new $this->filterClass($code, $filterType, $configuration['options'], $configuration['attributes']);
        $filter->setLabel($configuration['label']);
        if ($configuration['form_options']) {
            $filter->setFormOptions((array) $configuration['form_options']);
        }
        return $filter;
    }
}