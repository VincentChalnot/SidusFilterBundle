<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Query\Handler\Configuration;

use Sidus\FilterBundle\Filter\FilterInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;

/**
 * Holds the configuration of a query handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class QueryHandlerConfiguration implements QueryHandlerConfigurationInterface
{
    protected string $provider;

    /** @var array */
    protected array $sortable;

    /** @var FilterInterface[] */
    protected array $filters = [];

    /** @var array[] */
    protected array $defaultSort;

    protected int $resultsPerPage = 10;

    protected array $options = [];

    public function __construct(
        protected string $code,
        protected array $configuration
    ) {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $option) {
            $accessor->setValue($this, $key, $option);
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

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

    public function getFilter(string $code): FilterInterface
    {
        if (empty($this->filters[$code])) {
            throw new UnexpectedValueException("No filter with code : {$code} for query handler {$this->code}");
        }

        return $this->filters[$code];
    }

    public function getSortable(): array
    {
        return $this->sortable;
    }

    public function addSortable(string $sortable): void
    {
        $this->sortable[] = $sortable;
    }

    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    public function getResultsPerPage(): int
    {
        return $this->resultsPerPage;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $code, mixed $fallback = null): mixed
    {
        if (!array_key_exists($code, $this->options)) {
            return $fallback;
        }

        return $this->options[$code];
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function setSortable(array $sortable): void
    {
        $this->sortable = $sortable;
    }

    public function setDefaultSort(array $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }

    public function setResultsPerPage(int $resultsPerPage): void
    {
        $this->resultsPerPage = $resultsPerPage;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function addOption(string $code, mixed $value): void
    {
        $this->options[$code] = $value;
    }
}
