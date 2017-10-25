<?php

namespace Sidus\FilterBundle\Configuration;

use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Describes the bare minimum api to work with filters
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface FilterConfigurationHandlerInterface
{
    /**
     * @throws InvalidArgumentException
     *
     * @return array|\Traversable
     */
    public function getResults();

    /**
     * @throws InvalidArgumentException
     *
     * @return Pagerfanta
     */
    public function getPager();

    /**
     * @param FilterInterface $filter
     * @param int             $index
     */
    public function addFilter(FilterInterface $filter, $index = null);

    /**
     * @return FilterInterface[]
     */
    public function getFilters();

    /**
     * @param string $code
     *
     * @return FilterInterface
     */
    public function getFilter($code);

    /**
     * @return array
     */
    public function getSortable();

    /**
     * @param string $sortable
     */
    public function addSortable($sortable);

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request);

    /**
     * @param array $data
     */
    public function handleArray(array $data = []);

    /**
     * @return Form
     */
    public function getForm();

    /**
     * @return SortConfig
     */
    public function getSortConfig();

    /**
     * @param FormBuilderInterface $builder
     *
     * @return Form
     */
    public function buildForm(FormBuilderInterface $builder);
}
