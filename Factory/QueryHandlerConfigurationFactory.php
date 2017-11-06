<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfiguration;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Converts an array configuration into QueryHandlerConfiguration objects
 */
class QueryHandlerConfigurationFactory implements QueryHandlerConfigurationFactoryInterface
{
    /** @var FilterFactoryInterface */
    protected $filterFactory;

    /**
     * @param FilterFactoryInterface $filterFactory
     */
    public function __construct(FilterFactoryInterface $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\PropertyAccess\Exception\ExceptionInterface
     *
     * @return QueryHandlerConfigurationInterface
     */
    public function createQueryHandlerConfiguration(
        string $code,
        array $configuration
    ): QueryHandlerConfigurationInterface {
        /** @var array[] $filters */
        $filters = $configuration['filters'];
        unset($configuration['filters']);

        $queryHandlerConfiguration = new QueryHandlerConfiguration(
            $code,
            $configuration
        );

        foreach ($filters as $filterCode => $filterConfiguration) {
            $queryHandlerConfiguration->addFilter(
                $this->filterFactory->createFilter($queryHandlerConfiguration, $filterCode, $filterConfiguration)
            );
        }

        return $queryHandlerConfiguration;
    }
}
