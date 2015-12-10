<?php

    namespace Sidus\FilterBundle\Configuration;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Filter\FilterFactory;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class FilterConfigurationHandler
{
    const FILTERS_FORM_NAME = 'filters';
    const SORTABLE_FORM_NAME = 'sortable';
    const SORT_CONFIG_FORM_NAME = 'config';

    /** @var string */
    protected $code;

    /** @var Registry */
    protected $doctrine;

    /** @var FilterFactory */
    protected $filterFactory;

    /** @var string */
    protected $entityReference;

    /** @var array */
    protected $sortable;

    /** @var FilterInterface[] */
    protected $filters;

    /** @var Form */
    protected $form;

    /** @var EntityRepository */
    protected $repository;

    /** @var string */
    protected $alias;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var SortConfig */
    protected $sortConfig;

    /**
     * @param string $code
     * @param Registry $doctrine
     * @param FilterFactory $filterFactory
     * @param array $configuration
     * @throws UnexpectedValueException
     */
    public function __construct($code, Registry $doctrine, FilterFactory $filterFactory, array $configuration = [])
    {
        $this->code = $code;
        $this->doctrine = $doctrine;
        $this->filterFactory = $filterFactory;
        $this->parseConfiguration($configuration);
    }

    /**
     * @param FilterInterface $filter
     * @param int $index
     * @return FilterConfigurationHandler
     * @throws UnexpectedValueException
     */
    public function addFilter(FilterInterface $filter, $index = null)
    {
        if (null === $index) {
            $this->filters[$filter->getCode()] = $filter;
        } else {
            $count = count($this->filters);
            if (!is_int($index) && !is_numeric($index)) {
                throw new UnexpectedValueException("Given index should be an integer '{$index}' given");
            }
            if (abs($index) > $count) {
                $index = 0;
            }
            if ($index < 0) {
                $index = $count + $index;
            }
            /** @noinspection AdditionOperationOnArraysInspection */
            $this->filters = array_slice($this->filters, 0, $index, true) +
                [$filter->getCode() => $filter] +
                array_slice($this->filters, $index, $count - $index, true);
        }
        return $this;
    }

    /**
     * @return FilterInterface[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $code
     * @return FilterInterface
     * @throws UnexpectedValueException
     */
    public function getFilter($code)
    {
        if (empty($this->filters[$code])) {
            throw new UnexpectedValueException("No filter with code : {$code}");
        }
        return $this->filters[$code];
    }

    /**
     * @return array
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * @param string $sortable
     * @return FilterConfigurationHandler
     */
    public function addSortable($sortable)
    {
        $this->sortable[] = $sortable;
        return $this;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function handleRequest(Request $request)
    {
        $this->getForm()->handleRequest($request);
        $this->applyFilters($this->getQueryBuilder()); // @todo maybe do it in a form event ?
        $this->applySort($this->getQueryBuilder());
    }

    /**
     * @return Form
     * @throws \LogicException
     */
    public function getForm()
    {
        if (!$this->form) {
            throw new \LogicException("You must first build the form by calling buildForm(\$builder) with your form builder");
        }
        return $this->form;
    }

    /**
     * @param string $alias
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
     * @param $queryBuilder
     * @param string $alias
     * @return FilterConfigurationHandler
     */
    public function setQueryBuilder($queryBuilder, $alias)
    {
        $this->alias = $alias;
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * @param array $configuration
     * @throws UnexpectedValueException
     */
    protected function parseConfiguration(array $configuration)
    {
        $this->entityReference = $configuration['entity'];
        $this->repository = $this->doctrine->getRepository($this->entityReference);
        foreach ($configuration['fields'] as $code => $field) {
            $this->addFilter($this->filterFactory->create($code, $field));
        }
        $this->sortable = $configuration['sortable'];
        $this->sortConfig = new SortConfig();
    }

    /**
     * @param FormBuilder $builder
     * @return Form
     */
    public function buildForm(FormBuilder $builder)
    {
        $this->buildFilterForm($builder);
        $this->buildSortableForm($builder);

        $this->form = $builder->getForm();
        return $this->form;
    }

    /**
     * @param FormBuilder $builder
     */
    protected function buildFilterForm(FormBuilder $builder)
    {
        $filtersBuilder = $builder->create(self::FILTERS_FORM_NAME, 'form', [
            'label' => false,
        ]);
        foreach ($this->getFilters() as $filter) {
            $filtersBuilder->add($filter->getCode(), $filter->getFilterType()->getFormType(), $filter->getFormOptions());
        }
        $builder->add($filtersBuilder);
    }

    /**
     * @param FormBuilder $builder
     */
    protected function buildSortableForm(FormBuilder $builder)
    {
        $sortableBuilder = $builder->create(self::SORTABLE_FORM_NAME, 'form', [
            'label' => false,
        ]);
        $sortableBuilder->add(self::SORT_CONFIG_FORM_NAME, 'sidus_sort_config', [
            'data' => $this->sortConfig,
        ]);
        foreach ($this->getSortable() as $sortable) {
            $sortableBuilder->add($sortable, 'sidus_order_button', [
                'sort_config' => $this->sortConfig,
            ]);
        }
        $builder->add($sortableBuilder);
    }

    /**
     * @param QueryBuilder $qb
     * @throws \Exception
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
     * @throws \Exception
     */
    protected function applySort(QueryBuilder $qb)
    {
        $form = $this->getForm();
        $sortableForm = $form->get(self::SORTABLE_FORM_NAME);
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

        $column = $sortConfig->getColumn();
        if ($column) {
            $fullColumnReference = $column;
            if (false === strpos($column, '.')) {
                $fullColumnReference = $this->alias . '.' . $column;
            }
            $direction = $sortConfig->getDirection() ? 'DESC' : 'ASC'; // null or false both default to ASC
            $qb->addOrderBy($fullColumnReference, $direction);
        }
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}