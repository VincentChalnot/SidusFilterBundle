<?php

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Pagination\DoctrineORMAdapter;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\LessThan1MaxPerPageException;
use Pagerfanta\Exception\NotIntegerCurrentPageException;
use Pagerfanta\Exception\NotIntegerMaxPerPageException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Query\Handler\AbstractQueryHandler;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use UnexpectedValueException;

/**
 * Build the necessary logic around filters based on a configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineQueryHandler extends AbstractQueryHandler implements DoctrineQueryHandlerInterface
{
    /** @var Registry */
    protected $doctrine;

    /** @var string */
    protected $entityReference;

    /** @var EntityRepository */
    protected $repository;

    /** @var string */
    protected $alias = 'e';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @param FilterTypeRegistry                 $filterTypeRegistry
     * @param QueryHandlerConfigurationInterface $configuration
     * @param Registry                           $doctrine
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(
        FilterTypeRegistry $filterTypeRegistry,
        QueryHandlerConfigurationInterface $configuration,
        Registry $doctrine
    ) {
        parent::__construct($filterTypeRegistry, $configuration);
        $this->doctrine = $doctrine;
        $this->entityReference = $configuration->getOption('entity');
        if (null === $this->entityReference) {
            throw new UnexpectedValueException(
                "Missing 'entity' option for filter configuration {$configuration->getCode()}"
            );
        }
        $this->repository = $doctrine->getRepository($this->entityReference);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = $this->repository->createQueryBuilder($this->alias);
        }

        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, $alias)
    {
        $this->alias = $alias;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param int $selectedPage
     *
     * @throws LessThan1MaxPerPageException
     * @throws NotIntegerMaxPerPageException
     * @throws LessThan1CurrentPageException
     * @throws NotIntegerCurrentPageException
     * @throws OutOfRangeCurrentPageException
     */
    protected function applyPager($selectedPage = null)
    {
        if ($selectedPage) {
            $this->sortConfig->setPage($selectedPage);
        }
        $this->pager = new Pagerfanta(new DoctrineORMAdapter($this->getQueryBuilder()));
        $this->pager->setMaxPerPage($this->getConfiguration()->getResultsPerPage());
        try {
            $this->pager->setCurrentPage($this->sortConfig->getPage());
        } catch (NotValidCurrentPageException $e) {
            $this->sortConfig->setPage($this->pager->getCurrentPage());
        }
    }

    /**
     * @param SortConfig $sortConfig
     */
    protected function applySort(SortConfig $sortConfig)
    {
        $column = $sortConfig->getColumn();
        if ($column) {
            $fullColumnReference = $column;
            if (false === strpos($column, '.')) {
                $fullColumnReference = $this->alias.'.'.$column;
            }
            $direction = $sortConfig->getDirection() ? 'DESC' : 'ASC'; // null or false both default to ASC
            $this->getQueryBuilder()->addOrderBy($fullColumnReference, $direction);
        }
    }
}
