<?php

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Base logic common to all filter types
 */
interface FilterTypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return FormTypeInterface|string
     */
    public function getFormType();

    /**
     * @param FilterInterface $filter
     *
     * @return array
     */
    public function getFormOptions(FilterInterface $filter);
}
