<?php

namespace Sidus\FilterBundle\Query\Handler\Configuration;

use Sidus\FilterBundle\Filter\FilterInterface;
use UnexpectedValueException;

/**
 * Holds the configuration of a query handler
 */
class QueryHandlerConfiguration implements QueryHandlerConfigurationInterface
{
    /** @var string */
    protected $provider;

    /** @var string */
    protected $code;

    /** @var array */
    protected $sortable = [];

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var array[] */
    protected $defaultSort;

    /** @var int */
    protected $resultsPerPage;

    /**
     * @param string  $code
     * @param string  $provider
     * @param array   $filters
     * @param array   $sortable
     * @param array[] $defaultSort
     * @param int     $resultsPerPage
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(
        string $code,
        string $provider,
        array $filters,
        array $sortable,
        array $defaultSort,
        int $resultsPerPage = 15
    ) {
        $this->provider = $provider;
        $this->code = $code;
        $this->sortable = $sortable;
        $this->resultsPerPage = $resultsPerPage;
        $this->defaultSort = $defaultSort;

        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param FilterInterface $filter
     * @param int             $index
     *
     * @throws UnexpectedValueException
     */
    public function addFilter(FilterInterface $filter, int $index = null)
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
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param string $code
     *
     * @return FilterInterface
     * @throws UnexpectedValueException
     */
    public function getFilter(string $code): FilterInterface
    {
        if (empty($this->filters[$code])) {
            throw new UnexpectedValueException("No filter with code : {$code} for query handler {$this->code}");
        }

        return $this->filters[$code];
    }

    /**
     * @return array
     */
    public function getSortable(): array
    {
        return $this->sortable;
    }

    /**
     * @param string $sortable
     */
    public function addSortable(string $sortable)
    {
        $this->sortable[] = $sortable;
    }

    /**
     * @return array[]
     */
    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    /**
     * @return int
     */
    public function getResultsPerPage(): int
    {
        return $this->resultsPerPage;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
}
