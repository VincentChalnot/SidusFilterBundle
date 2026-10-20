<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;

/**
 * Computes the total of a list: the service named by `count:` in the list configuration (cached counts, a dedicated
 * optimised query...). Called lazily, at most once per result.
 */
interface CountStrategyInterface
{
    /** @return int|null null = unknown */
    public function count(QueryContextInterface $query, ListDefinition $list, ListInput $input): ?int;
}
