<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Input is a bare value (the first configured operator applies) or `{operator, value}`. The first entry of the
 * `operators` option is the default operator. Normalised value: `['operator' => string, 'value' => mixed]`.
 */
abstract class AbstractOperatorFilterType extends AbstractFilterType
{
    /** @return list<string> every operator this type knows */
    abstract protected function knownOperators(): array;

    /** @return list<string> operators that take no value */
    protected function valuelessOperators(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function defaultOperators(): array
    {
        return [$this->knownOperators()[0]];
    }

    /**
     * @return array{0: mixed}|Violation the normalised value (boxed in a one-element list) or the violation
     */
    abstract protected function normalizeValue(FilterDefinition $filter, string $operator, mixed $value): array|Violation;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $known = $this->knownOperators();
        $resolver->setDefault('operators', $this->defaultOperators());
        $resolver->setAllowedTypes('operators', 'string[]');
        $resolver->setAllowedValues('operators', static fn (array $operators): bool => [] !== $operators
            && [] === array_diff($operators, $known));
        $resolver->setNormalizer('operators', static fn ($options, array $operators): array => array_values(array_unique($operators)));
        $this->configureTypeOptions($resolver);
    }

    protected function configureTypeOptions(OptionsResolver $resolver): void
    {
    }

    final public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }
        /** @var list<string> $allowed */
        $allowed = $filter->options['operators'];
        $operator = $allowed[0];
        $value = $raw;
        if (\is_array($raw)) {
            if (!\array_key_exists('value', $raw) && !\array_key_exists('operator', $raw)) {
                return NormalizedValue::invalid(new Violation('', 'Expected a value or an {operator, value} map.', $raw));
            }
            $operator = $raw['operator'] ?? $operator;
            $value = $raw['value'] ?? null;
            if (!\is_string($operator) || !\in_array($operator, $allowed, true)) {
                return NormalizedValue::invalid(new Violation('operator', \sprintf('Unknown operator, allowed: %s.', implode(', ', $allowed)), $operator));
            }
        }
        if (\in_array($operator, $this->valuelessOperators(), true)) {
            return NormalizedValue::of(['operator' => $operator, 'value' => null]);
        }
        if (self::isBlank($value)) {
            return NormalizedValue::empty();
        }
        $normalized = $this->normalizeValue($filter, $operator, $value);
        if ($normalized instanceof Violation) {
            return NormalizedValue::invalid($normalized);
        }

        return NormalizedValue::of(['operator' => $operator, 'value' => $normalized[0]]);
    }

    /**
     * @return list<string> operators the definition allows
     */
    protected function allowedOperators(FilterDefinition $filter): array
    {
        return $filter->options['operators'];
    }
}
