<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;

final class ExactFilterType extends AbstractFilterType
{
    public static function getName(): string
    {
        return 'exact';
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return self::withAnyOf($filter, [Equals::class]);
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }
        if (!\is_scalar($raw)) {
            return NormalizedValue::invalid(new Violation('', 'Expected a scalar value.', $raw));
        }

        return NormalizedValue::of($raw);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $field): Equals => new Equals($field, $value));
    }
}
