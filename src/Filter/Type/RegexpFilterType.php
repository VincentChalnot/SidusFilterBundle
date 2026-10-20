<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Matches;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\PosixRegex;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Input\Violation;

final class RegexpFilterType extends AbstractOperatorFilterType
{
    public static function getName(): string
    {
        return 'regexp';
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        $negated = array_filter($filter->options['operators'], static fn (string $o): bool => str_starts_with($o, 'not_'));

        return self::withAnyOf($filter, [] === $negated ? [Matches::class] : [Matches::class, Not::class]);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        $options = $filter->providerOptions;
        ['operator' => $operator, 'value' => $pattern] = $value;
        $caseSensitive = !str_contains($operator, 'imatches');
        $negated = str_starts_with($operator, 'not_');

        return $this->forAttributes($filter, static function (Field $field) use ($pattern, $caseSensitive, $negated, $options): Criterion {
            $criterion = new Matches($field, $pattern, $caseSensitive, providerOptions: $options);

            return $negated ? new Not($criterion) : $criterion;
        });
    }

    protected static function knownOperators(): array
    {
        return ['matches', 'not_matches', 'imatches', 'not_imatches'];
    }

    protected static function defaultOperators(): array
    {
        return static::knownOperators();
    }

    protected function normalizeValue(FilterDefinition $filter, string $operator, mixed $value): array|Violation
    {
        if (!\is_string($value)) {
            return new Violation('value', 'Expected a pattern string.', $value);
        }
        $error = PosixRegex::validate($value);

        return null === $error ? [$value] : new Violation('value', $error, $value);
    }
}
