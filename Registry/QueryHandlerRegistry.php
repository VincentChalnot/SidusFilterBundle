<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Registry;

use Sidus\FilterBundle\Exception\MissingFilterException;
use Sidus\FilterBundle\Exception\MissingQueryHandlerException;
use Sidus\FilterBundle\Exception\MissingQueryHandlerFactoryException;
use Sidus\FilterBundle\Factory\QueryHandlerConfigurationFactoryInterface;
use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Holds all query handler configurations and how to build them
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class QueryHandlerRegistry
{
    /** @var QueryHandlerConfigurationFactoryInterface */
    protected $queryHandlerConfigurationFactory;

    /** @var QueryHandlerFactoryInterface[] */
    protected $queryHandlerFactories = [];

    /** @var array[] */
    protected $rawQueryHandlerConfigurations = [];

    /** @var QueryHandlerConfigurationInterface[] */
    protected $queryHandlerConfigurations = [];

    /** @var QueryHandlerInterface[] */
    protected $queryHandlers = [];

    /**
     * @param QueryHandlerConfigurationFactoryInterface $queryHandlerConfigurationFactory
     */
    public function __construct(
        QueryHandlerConfigurationFactoryInterface $queryHandlerConfigurationFactory
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
    public function addRawQueryHandlerConfiguration(string $code, array $configuration): void
    {
        $this->rawQueryHandlerConfigurations[$code] = $configuration;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     */
    public function addQueryHandlerConfiguration(QueryHandlerConfigurationInterface $queryHandlerConfiguration): void
    {
        $this->queryHandlerConfigurations[$queryHandlerConfiguration->getCode()] = $queryHandlerConfiguration;
    }

    /**
     * @param QueryHandlerInterface $queryHandler
     */
    public function addQueryHandler(QueryHandlerInterface $queryHandler): void
    {
        $this->queryHandlers[$queryHandler->getConfiguration()->getCode()] = $queryHandler;
    }

    /**
     * @param QueryHandlerFactoryInterface $queryHandlerFactory
     */
    public function addQueryHandlerFactory(QueryHandlerFactoryInterface $queryHandlerFactory): void
    {
        $this->queryHandlerFactories[$queryHandlerFactory->getProvider()] = $queryHandlerFactory;
    }

    /**
     * @param string $code
     *
     * @throws MissingFilterException
     * @throws MissingQueryHandlerException
     * @throws MissingQueryHandlerFactoryException
     *
     * @return QueryHandlerInterface
     */
    public function getQueryHandler(string $code): QueryHandlerInterface
    {
        if (!array_key_exists($code, $this->queryHandlers)) {
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
        return array_key_exists($code, $this->queryHandlers)
            || array_key_exists($code, $this->queryHandlerConfigurations)
            || array_key_exists($code, $this->rawQueryHandlerConfigurations);
    }

    /**
     * @param string $code
     *
     * @throws MissingQueryHandlerException
     *
     * @return QueryHandlerConfigurationInterface
     */
    protected function getQueryHandlerConfiguration(string $code): QueryHandlerConfigurationInterface
    {
        if (!array_key_exists($code, $this->queryHandlerConfigurations)) {
            return $this->buildQueryHandlerConfiguration($code);
        }

        return $this->queryHandlerConfigurations[$code];
    }

    /**
     * @param string $code
     *
     * @throws MissingFilterException
     * @throws MissingQueryHandlerException
     * @throws MissingQueryHandlerFactoryException
     *
     * @return QueryHandlerInterface
     */
    protected function buildQueryHandler(string $code): QueryHandlerInterface
    {
        $configuration = $this->getQueryHandlerConfiguration($code);
        $queryHandlerFactory = $this->getQueryHandlerFactory($configuration->getProvider());
        $queryHandler = $queryHandlerFactory->createQueryHandler($configuration);
        $this->queryHandlers[$code] = $queryHandler;
        unset($this->queryHandlerConfigurations[$code]);

        return $queryHandler;
    }

    /**
     * @param string $code
     *
     * @throws MissingQueryHandlerException
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
        unset($this->rawQueryHandlerConfigurations[$code]);

        return $queryHandlerConfiguration;
    }

    /**
     * @param string $provider
     *
     * @throws MissingQueryHandlerFactoryException
     *
     * @return QueryHandlerFactoryInterface
     */
    protected function getQueryHandlerFactory(string $provider): QueryHandlerFactoryInterface
    {
        if (!array_key_exists($provider, $this->queryHandlerFactories)) {
            throw new MissingQueryHandlerFactoryException($provider);
        }

        return $this->queryHandlerFactories[$provider];
    }
}
