<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Input\Violation;

final class NumberFilterType extends AbstractOperatorFilterType
{
    public static function getName(): string
    {
        return 'number';
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        $classes = [];
        foreach ($filter->options['operators'] as $operator) {
            array_push($classes, ...match ($operator) {
                'eq' => [Equals::class],
                'neq' => [Not::class, Equals::class],
                'is_null' => [IsNull::class],
                'is_not_null' => [Not::class, IsNull::class],
                default => [Range::class],
            });
        }

        return self::withAnyOf($filter, $classes);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        ['operator' => $operator, 'value' => $number] = $value;

        return $this->forAttributes($filter, static fn (Field $field): Criterion => match ($operator) {
            'eq' => new Equals($field, $number),
            'neq' => new Not(new Equals($field, $number)),
            'gt' => new Range($field, min: $number, minInclusive: false),
            'gte' => new Range($field, min: $number),
            'lt' => new Range($field, max: $number, maxInclusive: false),
            'lte' => new Range($field, max: $number),
            'is_null' => new IsNull($field),
            'is_not_null' => new Not(new IsNull($field)),
        });
    }

    protected static function knownOperators(): array
    {
        return ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'is_null', 'is_not_null'];
    }

    protected static function valuelessOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }

    protected function normalizeValue(FilterDefinition $filter, string $operator, mixed $value): array|Violation
    {
        if (\is_int($value) || (\is_float($value) && is_finite($value))) {
            return [$value];
        }
        if (\is_string($value) && is_numeric($value) && is_finite((float) $value)) {
            return [$value + 0];
        }

        return new Violation('value', 'Expected a number.', $value);
    }
}
