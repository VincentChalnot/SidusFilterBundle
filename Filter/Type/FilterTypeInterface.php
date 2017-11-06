<?php

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Base logic common to all filter types
 */
interface FilterTypeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getFormType(): string;

    /**
     * @param QueryHandlerInterface $queryHandler
     * @param FilterInterface       $filter
     * @param FormInterface         $form
     *
     * @throws \Sidus\FilterBundle\Exception\BadQueryHandlerException
     */
    public function handleForm(QueryHandlerInterface $queryHandler, FilterInterface $filter, FormInterface $form);

    /**
     * @param QueryHandlerInterface $queryHandler
     * @param FilterInterface       $filter
     *
     * @throws \Sidus\FilterBundle\Exception\BadQueryHandlerException
     *
     * @return array
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array;

    /**
     * @return string
     */
    public function getProvider(): string;
}
