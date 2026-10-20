<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Input `{from, to}`: ISO 8601 strings or DateTimeInterface. A date-only value (`2026-10-20`) is interpreted in the
 * `timezone` option (default UTC): `from` is the start of that day, `to` the last microsecond of it. Both bounds
 * are inclusive. Normalised value: `['from' => ?DateTimeImmutable, 'to' => ?DateTimeImmutable]`.
 */
final class DateRangeFilterType extends AbstractFilterType
{
    private const string DATE_ONLY = '/^\d{4}-\d{2}-\d{2}$/';
    private const string ISO = '/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?(Z|[+-]\d{2}:?\d{2})?)?$/';

    public static function getName(): string
    {
        return 'date_range';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('timezone', 'UTC');
        $resolver->setAllowedTypes('timezone', 'string');
        $resolver->setAllowedValues('timezone', static fn (string $tz): bool => \in_array($tz, \DateTimeZone::listIdentifiers(), true));
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        return $this->withAnyOf($filter, [Range::class]);
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }
        if (!\is_array($raw)) {
            return NormalizedValue::invalid(new Violation('', 'Expected a {from, to} map.', $raw));
        }
        $timezone = new \DateTimeZone($filter->options['timezone']);
        $violations = [];
        $bounds = [];
        foreach (['from', 'to'] as $bound) {
            $value = $raw[$bound] ?? null;
            if (self::isBlank($value)) {
                $bounds[$bound] = null;
                continue;
            }
            $date = $this->parse($value, 'to' === $bound, $timezone);
            if (null === $date) {
                $violations[] = new Violation($bound, 'Expected an ISO 8601 date or date-time.', $value);
            }
            $bounds[$bound] = $date;
        }
        if ([] !== $violations) {
            return NormalizedValue::invalid(...$violations);
        }
        if (null === $bounds['from'] && null === $bounds['to']) {
            return NormalizedValue::empty();
        }
        if (null !== $bounds['from'] && null !== $bounds['to'] && $bounds['from'] > $bounds['to']) {
            return NormalizedValue::invalid(new Violation('', '"from" must not be after "to".', $raw));
        }

        return NormalizedValue::of($bounds);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $field): Range => new Range($field, $value['from'], $value['to']));
    }

    private function parse(mixed $value, bool $endOfDay, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        if (!\is_string($value) || 1 !== preg_match(self::ISO, $value)) {
            return null;
        }
        try {
            if (1 === preg_match(self::DATE_ONLY, $value)) {
                $date = new \DateTimeImmutable($value, $timezone);
                if ($date->format('Y-m-d') !== $value) {
                    return null; // 2026-02-30 would silently roll over
                }

                return $date->setTime(...($endOfDay ? [23, 59, 59, 999999] : [0, 0, 0, 0]));
            }

            return new \DateTimeImmutable($value, $timezone);
        } catch (\Exception) {
            return null;
        }
    }
}
