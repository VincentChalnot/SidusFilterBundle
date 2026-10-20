<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\In;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One value, or several with `multiple: true`. `choices` is a list of values, or a map label => value; without
 * `choices` any scalar is accepted. Matching is by string comparison, the configured (typed) value is emitted.
 */
final class ChoiceFilterType extends AbstractFilterType
{
    public static function getName(): string
    {
        return 'choice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['choices' => [], 'multiple' => false]);
        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedValues('choices', static function (array $choices): bool {
            foreach ($choices as $choice) {
                if (!\is_scalar($choice)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        return $this->withAnyOf($filter, $filter->options['multiple'] ? [In::class] : [Equals::class]);
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }
        $multiple = $filter->options['multiple'];
        if (\is_array($raw)) {
            if (!$multiple) {
                return NormalizedValue::invalid(new Violation('', 'Only one value is allowed.', $raw));
            }
            $items = array_values($raw);
        } else {
            $items = [$raw];
        }

        $violations = [];
        $values = [];
        foreach ($items as $index => $item) {
            if (!\is_scalar($item)) {
                $violations[] = new Violation($multiple ? (string) $index : '', 'Expected a scalar value.', $item);
                continue;
            }
            $value = $this->resolve($filter->options['choices'], $item);
            if (null === $value) {
                $violations[] = new Violation($multiple ? (string) $index : '', 'This value is not a valid choice.', $item);
                continue;
            }
            $values[] = $value[0];
        }
        if ([] !== $violations) {
            return NormalizedValue::invalid(...$violations);
        }

        return NormalizedValue::of($multiple ? $values : $values[0]);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $field): Criterion => \is_array($value)
            ? new In($field, $value)
            : new Equals($field, $value));
    }

    /**
     * @param array<array-key, scalar> $choices
     *
     * @return array{0: scalar}|null
     */
    private function resolve(array $choices, mixed $item): ?array
    {
        if ([] === $choices) {
            return [$item];
        }
        foreach ($choices as $choice) {
            if ((string) $choice === (string) $item) {
                return [$choice];
            }
        }

        return null;
    }
}
