<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryContextInterface;

final class FixedCount implements CountStrategyInterface
{
    public function count(QueryContextInterface $query, ListDefinition $list, ListInput $input): ?int
    {
        return 777;
    }
}
