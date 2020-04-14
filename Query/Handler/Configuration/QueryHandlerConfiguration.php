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
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;
use function array_slice;
use function count;
use function is_int;

/**
 * Holds the configuration of a query handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class QueryHandlerConfiguration implements QueryHandlerConfigurationInterface
{
    /** @var string */
    protected $provider;

    /** @var string */
    protected $code;

    /** @var array */
    protected $sortable;

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var array[] */
    protected $defaultSort;

    /** @var int */
    protected $resultsPerPage;

    /** @var array */
    protected $options;

    /**
     * @param string $code
     * @param array  $configuration
     *
     * @throws ExceptionInterface
     */
    public function __construct(
        string $code,
        array $configuration
    ) {
        $this->code = $code;

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $option) {
            $accessor->setValue($this, $key, $option);
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
    public function addFilter(FilterInterface $filter, int $index = null): void
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
     * @throws UnexpectedValueException
     *
     * @return FilterInterface
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
    public function addSortable(string $sortable): void
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

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $code
     * @param mixed  $fallback
     *
     * @return mixed
     */
    public function getOption(string $code, $fallback = null)
    {
        if (!array_key_exists($code, $this->options)) {
            return $fallback;
        }

        return $this->options[$code];
    }

    /**
     * @param string $provider
     */
    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @param array $sortable
     */
    public function setSortable(array $sortable): void
    {
        $this->sortable = $sortable;
    }

    /**
     * @param FilterInterface[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @param array[] $defaultSort
     */
    public function setDefaultSort(array $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }

    /**
     * @param int $resultsPerPage
     */
    public function setResultsPerPage(int $resultsPerPage): void
    {
        $this->resultsPerPage = $resultsPerPage;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addOption(string $code, $value): void
    {
        $this->options[$code] = $value;
    }
}
