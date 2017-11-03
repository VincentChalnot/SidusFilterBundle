<?php

namespace Sidus\FilterBundle\Query\Handler\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\LessThan1MaxPerPageException;
use Pagerfanta\Exception\NotIntegerCurrentPageException;
use Pagerfanta\Exception\NotIntegerMaxPerPageException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Filter\Type\Doctrine\DoctrineFilterTypeInterface;
use Sidus\FilterBundle\Query\Handler\AbstractQueryHandler;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
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
     * @param QueryHandlerConfigurationInterface $configuration
     * @param Registry                           $doctrine
     */
    public function __construct(QueryHandlerConfigurationInterface $configuration, Registry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct($configuration);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Pagerfanta
     */
    public function getPager(): Pagerfanta
    {
        if (null === $this->pager) {
            $this->applyPager($this->getQueryBuilder());
        }

        return $this->pager;
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
     * @param QueryBuilder $qb
     *
     * @throws \LogicException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    protected function applyFilters(QueryBuilder $qb)
    {
        $form = $this->getForm();
        $filterForm = $form->get(self::FILTERS_FORM_NAME);
        foreach ($this->getConfiguration()->getFilters() as $filter) {
            $filterType = $filter->getFilterType();
            if (!$filterType instanceof DoctrineFilterTypeInterface) {
                throw new UnexpectedValueException(
                    "Filter {$this->getConfiguration()->getCode()}.{$filter->getCode()} has wrong filter type"
                );
            }
            $filterType->handleForm($filter, $filterForm->get($filter->getCode()), $qb, $this->alias);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param SortConfig   $sortConfig
     */
    protected function applySort(QueryBuilder $qb, SortConfig $sortConfig)
    {
        $column = $sortConfig->getColumn();
        if ($column) {
            $fullColumnReference = $column;
            if (false === strpos($column, '.')) {
                $fullColumnReference = $this->alias.'.'.$column;
            }
            $direction = $sortConfig->getDirection() ? 'DESC' : 'ASC'; // null or false both default to ASC
            $qb->addOrderBy($fullColumnReference, $direction);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param int          $selectedPage
     *
     * @throws LessThan1MaxPerPageException
     * @throws NotIntegerMaxPerPageException
     * @throws LessThan1CurrentPageException
     * @throws NotIntegerCurrentPageException
     * @throws OutOfRangeCurrentPageException
     */
    protected function applyPager(QueryBuilder $qb, $selectedPage = null)
    {
        if ($selectedPage) {
            $this->sortConfig->setPage($selectedPage);
        }
        $this->pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $this->pager->setMaxPerPage($this->getConfiguration()->getResultsPerPage());
        try {
            $this->pager->setCurrentPage($this->sortConfig->getPage());
        } catch (NotValidCurrentPageException $e) {
            $this->sortConfig->setPage($this->pager->getCurrentPage());
        }
    }

    /**
     * @param int $selectedPage
     *
     * @throws \LogicException
     * @throws \OutOfBoundsException
     * @throws LessThan1MaxPerPageException
     * @throws NotIntegerMaxPerPageException
     * @throws LessThan1CurrentPageException
     * @throws NotIntegerCurrentPageException
     * @throws OutOfRangeCurrentPageException
     * @throws \UnexpectedValueException
     */
    protected function handleForm($selectedPage = null)
    {
        $qb = $this->getQueryBuilder();
        $this->applyFilters($qb); // maybe do it in a form event ?
        $this->applySort($qb, $this->applySortForm());
        $this->applyPager($qb, $selectedPage); // merge with filters ?
    }
}
