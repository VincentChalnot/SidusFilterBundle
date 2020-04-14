<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Generic filter type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /**
     * @param string $name
     * @param string $formType
     * @param array  $formOptions
     */
    public function __construct(string $name, string $formType, array $formOptions = [])
    {
        $this->name = $name;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormType(QueryHandlerInterface $queryHandler, FilterInterface $filter): string
    {
        return $this->formType;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        return array_merge(
            $this->formOptions,
            $filter->getFormOptions()
        );
    }
}
