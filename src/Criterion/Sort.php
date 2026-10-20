<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/**
 * Ordering. Stable: the provider appends the identifier as final tie-breaker. Nulls sort first ascending and last
 * descending, as in PHP's `<=>`.
 */
final readonly class Sort implements Criterion
{
    /** @var list<OrderBy> */
    public array $columns;

    public function __construct(OrderBy ...$columns)
    {
        if ([] === $columns) {
            throw new \InvalidArgumentException('Sort requires at least one column.');
        }
        $this->columns = array_values($columns);
    }
}
