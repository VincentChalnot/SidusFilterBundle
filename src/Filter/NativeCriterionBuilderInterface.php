<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Definition\FilterDefinition;

/**
 * Service referenced by the `builder` option of a `native` filter: turns the filter's input into a provider-specific
 * criterion. Autoconfigured; the service id is the key.
 */
interface NativeCriterionBuilderInterface
{
    public function __invoke(FilterDefinition $filter, mixed $value): ?Native;
}
