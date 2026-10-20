<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;

final class NotEmptyFilterType extends AbstractFlagFilterType
{
    public static function getName(): string
    {
        return 'not_empty';
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        return $this->withAnyOf($filter, [Not::class, IsEmpty::class]);
    }

    protected function criterionFactory(): \Closure
    {
        return static fn (Field $field): Not => new Not(new IsEmpty($field));
    }
}
