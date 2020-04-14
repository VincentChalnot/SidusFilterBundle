<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Query\Handler\Configuration;

use Sidus\FilterBundle\Filter\FilterInterface;
use UnexpectedValueException;

/**
 * Holds the configuration of a query handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface QueryHandlerConfigurationInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return string
     */
    public function getProvider(): string;

    /**
     * @param FilterInterface $filter
     * @param int             $index
     *
     * @throws UnexpectedValueException
     */
    public function addFilter(FilterInterface $filter, int $index = null);

    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array;

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return FilterInterface
     */
    public function getFilter(string $code): FilterInterface;

    /**
     * @return array
     */
    public function getSortable(): array;

    /**
     * @param string $sortable
     */
    public function addSortable(string $sortable);

    /**
     * @return array[]
     */
    public function getDefaultSort(): array;

    /**
     * @return int
     */
    public function getResultsPerPage(): int;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @param string $code
     * @param null   $fallback
     *
     * @return mixed
     */
    public function getOption(string $code, $fallback = null);
}
