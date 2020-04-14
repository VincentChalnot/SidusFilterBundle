<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Query\Handler;

use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Describes the bare minimum api to work with filters
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface QueryHandlerInterface
{
    /**
     * @return QueryHandlerConfigurationInterface
     */
    public function getConfiguration(): QueryHandlerConfigurationInterface;

    /**
     * @throws InvalidArgumentException
     *
     * @return Pagerfanta
     */
    public function getPager(): Pagerfanta;

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request);

    /**
     * @param array $data
     */
    public function handleArray(array $data = []);

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface;

    /**
     * @return SortConfig
     */
    public function getSortConfig(): SortConfig;

    /**
     * @param FormBuilderInterface $builder
     *
     * @return FormInterface
     */
    public function buildForm(FormBuilderInterface $builder): FormInterface;
}
