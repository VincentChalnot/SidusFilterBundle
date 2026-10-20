<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\OrderBy;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Criterion\SortDirection;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sorting as a filter (section 9). The input names public column keys, never property paths:
 *  - `-date,title` (a leading `-` is descending, a leading `+` or nothing ascending);
 *  - `[{column: date, direction: desc}, 'title']`, or a `{column, direction}` map, or `{date: desc}`.
 *
 * Options: `columns` (key => field: a path string, a `{path, json, cast}` map, or `{field, provider_options}`),
 * `default` (list of `{column, direction}` applied when the input has no valid sort), `max_columns` (default 3).
 * Normalised value: `list<array{column: string, direction: 'asc'|'desc'}>`.
 */
final class SortFilterType extends AbstractFilterType
{
    public static function getName(): string
    {
        return 'sort';
    }

    public static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('columns');
        $resolver->setDefaults(['default' => [], 'max_columns' => 3]);
        $resolver->setAllowedTypes('columns', 'array');
        $resolver->setAllowedTypes('default', 'array');
        $resolver->setAllowedTypes('max_columns', 'int');
        $resolver->setAllowedValues('max_columns', static fn (int $max): bool => $max >= 1);
        $resolver->setNormalizer('columns', static function (Options $options, array $columns): array {
            if ([] === $columns) {
                throw new \InvalidArgumentException('At least one sort column is required.');
            }
            $resolved = [];
            foreach ($columns as $key => $column) {
                $key = (string) $key;
                if (1 !== preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                    throw new \InvalidArgumentException(\sprintf('Invalid sort column key "%s": use letters, digits and underscores.', $key));
                }
                $providerOptions = [];
                if (\is_array($column) && \array_key_exists('field', $column)) {
                    $providerOptions = $column['provider_options'] ?? [];
                    $column = $column['field'];
                }
                $resolved[$key] = ['field' => Field::fromConfig($column)->toArray(), 'provider_options' => $providerOptions];
            }

            return $resolved;
        });
        $resolver->setNormalizer('default', static function (Options $options, array $default): array {
            $resolved = [];
            foreach ($default as $item) {
                $column = \is_array($item) ? ($item['column'] ?? null) : null;
                $direction = \is_array($item) ? ($item['direction'] ?? 'asc') : 'asc';
                if (!\is_string($column) || !isset($options['columns'][$column])) {
                    throw new \InvalidArgumentException(\sprintf('The default sort references an unknown column, available: %s.', implode(', ', array_keys($options['columns']))));
                }
                if (null === SortDirection::tryFrom((string) $direction)) {
                    throw new \InvalidArgumentException(\sprintf('Invalid default sort direction "%s" (asc or desc).', (string) $direction));
                }
                $resolved[] = ['column' => $column, 'direction' => $direction];
            }

            return $resolved;
        });
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return [Sort::class];
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return [] === $filter->options['default'] ? NormalizedValue::empty() : NormalizedValue::of($filter->options['default']);
        }
        $items = $this->items($raw);
        if (null === $items) {
            return NormalizedValue::invalid(new Violation('', 'Expected "-date,title" or a list of {column, direction}.', $raw));
        }
        $violations = [];
        $columns = [];
        foreach ($items as $index => [$column, $direction]) {
            if (!\is_string($column) || !isset($filter->options['columns'][$column])) {
                $violations[] = new Violation((string) $index, \sprintf('Unknown sort column, allowed: %s.', implode(', ', array_keys($filter->options['columns']))), $column);
                continue;
            }
            if (null === ($dir = SortDirection::tryFrom(\is_string($direction) ? strtolower($direction) : ''))) {
                $violations[] = new Violation((string) $index, 'Invalid direction (asc or desc).', $direction);
                continue;
            }
            if (isset($columns[$column])) {
                $violations[] = new Violation((string) $index, 'Duplicate sort column.', $column);
                continue;
            }
            $columns[$column] = ['column' => $column, 'direction' => $dir->value];
        }
        if (\count($items) > $filter->options['max_columns']) {
            $violations[] = new Violation('', \sprintf('At most %d sort columns are allowed.', $filter->options['max_columns']), $raw);
        }
        if ([] !== $violations) {
            return NormalizedValue::invalid(...$violations);
        }

        return NormalizedValue::of(array_values($columns));
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        $columns = $filter->options['columns'];

        return new Sort(...array_map(static fn (array $item): OrderBy => new OrderBy(
            Field::fromArray($columns[$item['column']]['field']),
            SortDirection::from($item['direction']),
            $columns[$item['column']]['provider_options'],
        ), $value));
    }

    /**
     * @return list<array{0: mixed, 1: mixed}>|null list of [column, direction]
     */
    private function items(mixed $raw): ?array
    {
        if (\is_string($raw)) {
            $items = [];
            foreach (explode(',', $raw) as $token) {
                $token = trim($token);
                if ('' === $token) {
                    continue;
                }
                $items[] = str_starts_with($token, '-') ? [substr($token, 1), 'desc'] : [ltrim($token, '+ '), 'asc'];
            }

            return [] === $items ? null : $items;
        }
        if (!\is_array($raw)) {
            return null;
        }
        if (\array_key_exists('column', $raw)) {
            return [[$raw['column'], $raw['direction'] ?? 'asc']];
        }
        $items = [];
        foreach ($raw as $key => $item) {
            if (\is_string($key)) {
                $items[] = [$key, $item];
            } elseif (\is_string($item)) {
                array_push($items, ...($this->items($item) ?? []));
            } elseif (\is_array($item) && \array_key_exists('column', $item)) {
                $items[] = [$item['column'], $item['direction'] ?? 'asc'];
            } else {
                return null;
            }
        }

        return $items;
    }
}
