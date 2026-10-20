<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NativeCriterionBuilderInterface;

/**
 * A native in_memory criterion: keeps the items whose id has the parity given in the input.
 */
final class EvenIdBuilder implements NativeCriterionBuilderInterface
{
    public function __invoke(FilterDefinition $filter, mixed $value): ?Native
    {
        $even = 'even' === $value;

        return new Native('in_memory', static fn (array $item): bool => ($item['id'] % 2 === 0) === $even);
    }
}
