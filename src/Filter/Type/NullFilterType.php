<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;

final class NullFilterType extends AbstractFlagFilterType
{
    public static function getName(): string
    {
        return 'null';
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        return $this->withAnyOf($filter, [IsNull::class]);
    }

    protected function criterionFactory(): \Closure
    {
        return static fn (Field $field): IsNull => new IsNull($field);
    }
}
