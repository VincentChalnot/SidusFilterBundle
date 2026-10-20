<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\EndsWith;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\StartsWith;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Input\Violation;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TextFilterType extends AbstractOperatorFilterType
{
    private const array OPERATORS = [
        'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with',
        'equals', 'not_equals', 'is_null', 'is_not_null', 'is_empty', 'is_not_empty',
    ];

    public static function getName(): string
    {
        return 'text';
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        $classes = [];
        foreach ($filter->options['operators'] as $operator) {
            $classes[] = match (self::base($operator)) {
                'contains' => Contains::class,
                'starts_with' => StartsWith::class,
                'ends_with' => EndsWith::class,
                'equals' => Equals::class,
                'null' => IsNull::class,
                default => IsEmpty::class,
            };
            if (self::isNegated($operator)) {
                $classes[] = Not::class;
            }
        }

        return $this->withAnyOf($filter, $classes);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        ['operator' => $operator, 'value' => $text] = $value;
        $caseSensitive = $filter->options['case_sensitive'];
        $negated = self::isNegated($operator);
        $base = self::base($operator);

        return $this->forAttributes($filter, static function (Field $field) use ($base, $text, $caseSensitive, $negated): Criterion {
            $criterion = match ($base) {
                'contains' => new Contains($field, $text, $caseSensitive),
                'starts_with' => new StartsWith($field, $text, $caseSensitive),
                'ends_with' => new EndsWith($field, $text, $caseSensitive),
                'equals' => new Equals($field, $text),
                'null' => new IsNull($field),
                default => new IsEmpty($field),
            };

            return $negated ? new Not($criterion) : $criterion;
        });
    }

    /** `not_contains` => `contains`, `is_not_null` => `null`, `is_empty` => `empty`. */
    private static function base(string $operator): string
    {
        return preg_replace('/^(is_not_|is_|not_)/', '', $operator);
    }

    private static function isNegated(string $operator): bool
    {
        return str_starts_with($operator, 'not_') || str_starts_with($operator, 'is_not_');
    }

    protected function knownOperators(): array
    {
        return self::OPERATORS;
    }

    protected function defaultOperators(): array
    {
        return ['contains'];
    }

    protected function valuelessOperators(): array
    {
        return ['is_null', 'is_not_null', 'is_empty', 'is_not_empty'];
    }

    protected function configureTypeOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('case_sensitive', false);
        $resolver->setAllowedTypes('case_sensitive', 'bool');
    }

    protected function normalizeValue(FilterDefinition $filter, string $operator, mixed $value): array|Violation
    {
        if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !$value instanceof \Stringable) {
            return new Violation('value', 'Expected a string.', $value);
        }

        return [(string) $value];
    }
}
