<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfiguration;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Converts an array configuration into QueryHandlerConfiguration objects
 */
class QueryHandlerConfigurationFactory implements QueryHandlerConfigurationFactoryInterface
{
    /** @var FilterFactoryInterface[] */
    protected $filterFactories;

    /**
     * @param FilterFactoryInterface $factory
     */
    public function addFilterFactory(FilterFactoryInterface $factory)
    {
        $this->filterFactories[$factory->getProvider()] = $factory;
    }

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws \UnexpectedValueException
     *
     * @return QueryHandlerConfigurationInterface
     */
    public function createQueryHandlerConfiguration(
        string $code,
        array $configuration
    ): QueryHandlerConfigurationInterface {
        $filters = [];
        $filterFactory = $this->getFilterFactory($configuration['provider']);
        foreach ((array) $configuration['filters'] as $filterCode => $filterConfiguration) {
            $filters[$filterCode] = $filterFactory->createFilter($filterCode, $configuration);
        }

        return new QueryHandlerConfiguration(
            $code,
            $configuration['provider'],
            $filters,
            $configuration['sortable'],
            $configuration['default_sort'],
            $configuration['results_per_page']
        );
    }

    /**
     * @param string $provider
     *
     * @return FilterFactoryInterface
     * @throws \UnexpectedValueException
     */
    protected function getFilterFactory(string $provider): FilterFactoryInterface
    {
        if (!array_key_exists($provider, $this->filterFactories)) {
            throw new \UnexpectedValueException("No matching factory for unknown filter provider {$provider}");
        }

        return $this->filterFactories[$provider];
    }
}
