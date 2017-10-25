<?php

namespace Sidus\FilterBundle\Filter\Doctrine;

use Sidus\FilterBundle\Configuration\FilterTypeConfigurationHandler;

/**
 * Factory for doctrine filters
 */
class DoctrineFilterFactory
{
    /** @var string */
    protected $filterClass;

    /** @var FilterTypeConfigurationHandler */
    protected $filterTypeConfigurationHandler;

    /**
     * @param string                         $filterClass
     * @param FilterTypeConfigurationHandler $filterTypeConfigurationHandler
     */
    public function __construct($filterClass, FilterTypeConfigurationHandler $filterTypeConfigurationHandler)
    {
        $this->filterClass = $filterClass;
        $this->filterTypeConfigurationHandler = $filterTypeConfigurationHandler;
    }

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws \UnexpectedValueException
     *
     * @return DoctrineFilterInterface
     */
    public function create($code, array $configuration)
    {
        $filterType = $this->filterTypeConfigurationHandler->getFilterType($configuration['type']);
        $options = empty($configuration['options']) ? [] : $configuration['options'];
        $attributes = empty($configuration['attributes']) ? [] : $configuration['attributes'];
        $filterClass = $this->filterClass;
        /** @var DoctrineFilterInterface $filter */
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
