<?php

namespace Sidus\FilterBundle\Query\Handler;

use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Form\Type\OrderButtonType;
use Sidus\FilterBundle\Form\Type\SortConfigType;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

/**
 * Build the necessary logic around filters based on a configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractQueryHandler implements QueryHandlerInterface
{
    const FILTERS_FORM_NAME = 'filters';
    const SORTABLE_FORM_NAME = 'sortable';
    const SORT_CONFIG_FORM_NAME = 'config';

    /** @var QueryHandlerConfigurationInterface */
    protected $configuration;

    /** @var Form */
    protected $form;

    /** @var SortConfig */
    protected $sortConfig;

    /** @var Pagerfanta */
    protected $pager;

    /**
     * @param QueryHandlerConfigurationInterface $configuration
     */
    public function __construct(QueryHandlerConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->sortConfig = new SortConfig();
    }

    /**
     * @return QueryHandlerConfigurationInterface
     */
    public function getConfiguration(): QueryHandlerConfigurationInterface
    {
        return $this->configuration;
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
     * @return FormInterface
     * @throws \LogicException
     */
    public function getForm(): FormInterface
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
    public function getSortConfig(): SortConfig
    {
        return $this->sortConfig;
    }

    /**
     * @param FormBuilderInterface $builder
     *
     * @return FormInterface
     */
    public function buildForm(FormBuilderInterface $builder): FormInterface
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
    abstract public function getPager(): Pagerfanta;

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
        foreach ($this->getConfiguration()->getSortable() as $sortable) {
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

        foreach ($this->getConfiguration()->getSortable() as $sortable) {
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
        foreach ($this->getConfiguration()->getFilters() as $filter) {
            $filtersBuilder->add($filter->getCode(), $filter->getFormType(), $filter->getFormOptions());
        }
        $builder->add($filtersBuilder);
    }

    /**
     * @param int $selectedPage
     */
    abstract protected function handleForm($selectedPage = null);
}
