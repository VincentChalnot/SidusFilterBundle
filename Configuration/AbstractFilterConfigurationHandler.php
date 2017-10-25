<?php

namespace Sidus\FilterBundle\Configuration;

use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\OrderButtonType;
use Sidus\FilterBundle\Form\Type\SortConfigType;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

/**
 * Build the necessary logic around filters based on a configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractFilterConfigurationHandler implements FilterConfigurationHandlerInterface
{
    const FILTERS_FORM_NAME = 'filters';
    const SORTABLE_FORM_NAME = 'sortable';
    const SORT_CONFIG_FORM_NAME = 'config';

    /** @var string */
    protected $code;

    /** @var array */
    protected $sortable = [];

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var Form */
    protected $form;

    /** @var SortConfig */
    protected $sortConfig;

    /** @var Pagerfanta */
    protected $pager;

    /** @var int */
    protected $resultsPerPage;

    /**
     * @param string $code
     * @param array  $configuration
     */
    public function __construct($code, array $configuration)
    {
        $this->code = $code;
        $this->sortable = $configuration['sortable'];
        $this->resultsPerPage = $configuration['results_per_page'];
        $this->sortConfig = new SortConfig();

        /** @noinspection ForeachSourceInspection */
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($configuration['default_sort'] as $column => $direction) {
            $this->sortConfig->setDefaultColumn($column);
            $this->sortConfig->setDefaultDirection($direction === 'DESC');
            break;
        }
    }


    /**
     * @throws InvalidArgumentException
     *
     * @return array|\Traversable
     */
    public function getResults()
    {
        return $this->getPager()->getCurrentPageResults();
    }

    /**
     * @param FilterInterface $filter
     * @param int             $index
     *
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
                $index += $count;
            }
            /** @noinspection AdditionOperationOnArraysInspection */
            $this->filters = array_slice($this->filters, 0, $index, true) +
                [$filter->getCode() => $filter] +
                array_slice($this->filters, $index, $count - $index, true);
        }
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
     *
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
     */
    public function addSortable($sortable)
    {
        $this->sortable[] = $sortable;
    }

    /**
     * @param Request $request
     *
     * @throws \LogicException
     * @throws \OutOfBoundsException
     * @throws NotValidCurrentPageException
     */
    public function handleRequest(Request $request)
    {
        $this->getForm()->handleRequest($request);
        $this->handleForm($request->get('page'));
    }

    /**
     * @param array $data
     *
     * @throws \LogicException
     * @throws \OutOfBoundsException
     * @throws AlreadySubmittedException
     * @throws NotValidCurrentPageException
     */
    public function handleArray(array $data = [])
    {
        $this->getForm()->submit($data);
        $this->handleForm($data['page'] ?? null);
    }

    /**
     * @return Form
     * @throws \LogicException
     */
    public function getForm()
    {
        if (!$this->form) {
            throw new \LogicException(
                'You must first build the form by calling buildForm($builder) with your form builder'
            );
        }

        return $this->form;
    }

    /**
     * @return SortConfig
     */
    public function getSortConfig()
    {
        return $this->sortConfig;
    }

    /**
     * @param FormBuilderInterface $builder
     *
     * @return Form
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $this->buildFilterForm($builder);
        $this->buildSortableForm($builder);

        $this->form = $builder->getForm();

        return $this->form;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Pagerfanta
     */
    abstract public function getPager();

    /**
     * @param FormBuilderInterface $builder
     */
    protected function buildSortableForm(FormBuilderInterface $builder)
    {
        $sortableBuilder = $builder->create(
            self::SORTABLE_FORM_NAME,
            FormType::class,
            [
                'label' => false,
            ]
        );
        $sortableBuilder->add(
            self::SORT_CONFIG_FORM_NAME,
            SortConfigType::class,
            [
                'data' => $this->sortConfig,
            ]
        );
        foreach ($this->getSortable() as $sortable) {
            $sortableBuilder->add(
                $sortable,
                OrderButtonType::class,
                [
                    'sort_config' => $this->sortConfig,
                ]
            );
        }
        $builder->add($sortableBuilder);
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
            $filtersBuilder->add($filter->getCode(), $filter->getFormType(), $filter->getFormOptions());
        }
        $builder->add($filtersBuilder);
    }

    /**
     * @param int $selectedPage
     */
    abstract protected function handleForm($selectedPage = null);
}
