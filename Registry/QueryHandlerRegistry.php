<?php

namespace Sidus\FilterBundle\Registry;

use Sidus\FilterBundle\Exception\MissingFilterException;
use Sidus\FilterBundle\Exception\MissingQueryHandlerException;
use Sidus\FilterBundle\Factory\QueryHandlerConfigurationFactoryInterface;
use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Holds all query handler configurations and how to build them
 */
class QueryHandlerRegistry
{
    /** @var QueryHandlerConfigurationFactoryInterface */
    protected $queryHandlerConfigurationFactory;

    /** @var QueryHandlerFactoryInterface */
    protected $queryHandlerFactory;

    /** @var array[] */
    protected $rawQueryHandlerConfigurations;

    /** @var QueryHandlerConfigurationInterface[] */
    protected $queryHandlerConfigurations = [];

    /** @var QueryHandlerInterface[] */
    protected $queryHandlers = [];

    /**
     * @param QueryHandlerConfigurationFactoryInterface $queryHandlerConfigurationFactory
     * @param QueryHandlerFactoryInterface              $queryHandlerFactory
     */
    public function __construct(
        QueryHandlerConfigurationFactoryInterface $queryHandlerConfigurationFactory,
        QueryHandlerFactoryInterface $queryHandlerFactory
    ) {
        $this->queryHandlerConfigurationFactory = $queryHandlerConfigurationFactory;
    }

    /**
     * Used by the dependency injection system to add raw configuration items from YML config
     *
     * @param string $code
     * @param array  $configuration
     *
     * @internal Warning this method does not validate the given configuration
     */
    public function addRawQueryHandlerConfiguration(string $code, array $configuration)
    {
        $this->rawQueryHandlerConfigurations[$code] = $configuration;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     */
    public function addQueryHandlerConfiguration(QueryHandlerConfigurationInterface $queryHandlerConfiguration)
    {
        $this->queryHandlerConfigurations[$queryHandlerConfiguration->getCode()][$queryHandlerConfiguration];
    }

    /**
     * @param QueryHandlerInterface $queryHandler
     */
    public function addQueryHandler(QueryHandlerInterface $queryHandler)
    {
        $this->queryHandlers[$queryHandler->getConfiguration()->getCode()] = $queryHandler;
    }

    /**
     * @param string $code
     *
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerException
     *
     * @return QueryHandlerConfigurationInterface
     */
    public function getQueryHandlerConfiguration(string $code): QueryHandlerConfigurationInterface
    {
        if (!array_key_exists($code, $this->queryHandlerConfigurations)) {
            return $this->buildQueryHandlerConfiguration($code);
        }

        return $this->queryHandlerConfigurations[$code];
    }

    /**
     * @param string $code
     *
     * @throws \UnexpectedValueException
     * @throws MissingFilterException
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerException
     *
     * @return QueryHandlerInterface
     */
    public function getQueryHandler(string $code): QueryHandlerInterface
    {
        if (!$this->hasQueryHandler($code)) {
            return $this->buildQueryHandler($code);
        }

        return $this->queryHandlers[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasQueryHandler(string $code): bool
    {
        return array_key_exists($code, $this->queryHandlers);
    }

    /**
     * @param string $code
     *
     * @throws \Sidus\FilterBundle\Exception\MissingFilterException
     * @throws \UnexpectedValueException
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerException
     *
     * @return QueryHandlerInterface
     */
    protected function buildQueryHandler(string $code): QueryHandlerInterface
    {
        $configuration = $this->getQueryHandlerConfiguration($code);
        $queryHandlder = $this->queryHandlerFactory->createQueryHandler($configuration);
        $this->queryHandlers[$code] = $queryHandlder;

        return $queryHandlder;
    }

    /**
     * @param string $code
     *
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerException
     *
     * @return QueryHandlerConfigurationInterface
     */
    protected function buildQueryHandlerConfiguration(string $code): QueryHandlerConfigurationInterface
    {
        if (!array_key_exists($code, $this->rawQueryHandlerConfigurations)) {
            throw new MissingQueryHandlerException($code);
        }
        $queryHandlerConfiguration = $this->queryHandlerConfigurationFactory->createQueryHandlerConfiguration(
            $code,
            $this->rawQueryHandlerConfigurations[$code]
        );
        $this->queryHandlerConfigurations[$code] = $queryHandlerConfiguration;

        return $queryHandlerConfiguration;
    }
}
