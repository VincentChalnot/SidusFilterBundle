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

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return self::withAnyOf($filter, [IsNull::class]);
    }

    protected function criterionFactory(array $providerOptions): \Closure
    {
        return static fn (Field $field): IsNull => new IsNull($field, $providerOptions);
    }
}
