<?php

namespace Sidus\FilterBundle\Query\Handler\Configuration;

use Sidus\FilterBundle\Filter\FilterInterface;
use UnexpectedValueException;

/**
 * Holds the configuration of a query handler
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
     * @return FilterInterface
     * @throws UnexpectedValueException
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
}
