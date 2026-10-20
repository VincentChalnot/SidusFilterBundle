<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;

final class NotNullFilterType extends AbstractFlagFilterType
{
    public static function getName(): string
    {
        return 'not_null';
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return self::withAnyOf($filter, [Not::class, IsNull::class]);
    }

    protected function criterionFactory(array $providerOptions): \Closure
    {
        return static fn (Field $field): Not => new Not(new IsNull($field, $providerOptions));
    }
}
