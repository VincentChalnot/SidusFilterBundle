<?php

namespace Sidus\FilterBundle\Factory\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandler;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

/**
 * Dedicated logic for Doctrine query handler
 */
class DoctrineQueryHandlerFactory implements QueryHandlerFactoryInterface
{
    /** @var FilterTypeRegistry */
    protected $filterTypeRegistry;

    /** @var Registry */
    protected $doctrine;

    /**
     * @param FilterTypeRegistry $filterTypeRegistry
     * @param Registry           $doctrine
     */
    public function __construct(FilterTypeRegistry $filterTypeRegistry, Registry $doctrine)
    {
        $this->filterTypeRegistry = $filterTypeRegistry;
        $this->doctrine = $doctrine;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     *
     * @throws \UnexpectedValueException
     *
     * @return QueryHandlerInterface
     */
    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface {
        return new DoctrineQueryHandler($this->filterTypeRegistry, $queryHandlerConfiguration, $this->doctrine);
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'doctrine';
    }
}
