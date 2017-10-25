<?php

namespace Sidus\FilterBundle\Configuration;

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
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterFactory;
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use UnexpectedValueException;

/**
 * Build the necessary logic around filters based on a configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @method DoctrineFilterInterface[] getFilters
 */
class DoctrineFilterConfigurationHandler extends AbstractFilterConfigurationHandler implements DoctrineFilterConfigurationHandlerInterface
{
    /** @var Registry */
    protected $doctrine;

    /** @var DoctrineFilterFactory */
    protected $filterFactory;

    /** @var string */
    protected $entityReference;

    /** @var EntityRepository */
    protected $repository;

    /** @var string */
    protected $alias;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @param string                $code
     * @param Registry              $doctrine
     * @param DoctrineFilterFactory $filterFactory
     * @param array                 $configuration
     *
     * @throws UnexpectedValueException
     */
    public function __construct(
        $code,
        Registry $doctrine,
        DoctrineFilterFactory $filterFactory,
        array $configuration = []
    ) {
        parent::__construct($code, $configuration);
        $this->doctrine = $doctrine;
        $this->filterFactory = $filterFactory;
        $this->parseConfiguration($configuration);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Pagerfanta
     */
    public function getPager()
    {
        if (null === $this->pager) {
            $this->applyPager($this->getQueryBuilder());
        }

        return $this->pager;
    }

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder($alias = 'e')
    {
        if (!$this->queryBuilder) {
            $this->alias = $alias;
            $this->queryBuilder = $this->repository->createQueryBuilder($alias);
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
     * @param FormBuilderInterface $builder
     */
    protected function buildFilterForm(FormBuilderInterface $builder)
    {
        $filtersBuilder = $builder->create(
            self::FILTERS_FORM_NAME,
            FormType::class,
            [
                'label' => false,
            ]
        );
        foreach ($this->getFilters() as $filter) {
            $options = $filter->getDoctrineFormOptions($this->getQueryBuilder(), $this->getAlias());
            $filtersBuilder->add($filter->getCode(), $filter->getFormType(), $options);
        }
        $builder->add($filtersBuilder);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @throws \LogicException
     * @throws \OutOfBoundsException
     */
    protected function applyFilters(QueryBuilder $qb)
    {
        $form = $this->getForm();
        $filterForm = $form->get(self::FILTERS_FORM_NAME);
        foreach ($this->getFilters() as $filter) {
            $filter->handleForm($filterForm->get($filter->getCode()), $qb, $this->alias);
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
     * @todo : Put in form event ?
     * @throws \LogicException
     * @throws \OutOfBoundsException
     */
    protected function applySortForm()
    {
        $form = $this->getForm();
        $sortableForm = $form->get(self::SORTABLE_FORM_NAME);
        /** @var FormInterface $sortConfigForm */
        $sortConfigForm = $sortableForm->get(self::SORT_CONFIG_FORM_NAME);
        /** @var SortConfig $sortConfig */
        $sortConfig = $sortConfigForm->getData();

        foreach ($this->getSortable() as $sortable) {
            /** @var SubmitButton $button */
            $button = $sortableForm->get($sortable);
            if ($button->isClicked()) {
                if ($sortConfig->getColumn() === $sortable) {
                    $sortConfig->switchDirection();
                } else {
                    $sortConfig->setColumn($sortable);
                    $sortConfig->setDirection(false);
                }
            }
        }

        return $sortConfig;
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
        $this->pager->setMaxPerPage($this->resultsPerPage);
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
     */
    protected function handleForm($selectedPage = null)
    {
        $qb = $this->getQueryBuilder();
        $this->applyFilters($qb); // maybe do it in a form event ?
        $this->applySort($qb, $this->applySortForm());
        $this->applyPager($qb, $selectedPage); // merge with filters ?
    }

    /**
     * @param array $configuration
     *
     * @throws UnexpectedValueException
     */
    protected function parseConfiguration(array $configuration)
    {
        $this->entityReference = $configuration['entity'];
        $this->repository = $this->doctrine->getRepository($this->entityReference);
        /** @noinspection ForeachSourceInspection */
        foreach ($configuration['fields'] as $code => $field) {
            $this->addFilter($this->filterFactory->create($code, $field));
        }
    }
}
