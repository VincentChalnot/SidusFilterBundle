<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Definition;

/**
 * A list: a provider, its options, pagination, count strategy and the filters active for that provider. Built once
 * (at compile time), immutable, shared by every request.
 */
final readonly class ListDefinition
{
    public const string COUNT_AUTO = 'auto';
    public const string COUNT_NONE = 'none';

    /** @var list<FilterDefinition> */
    public array $filters;

    /**
     * @param array<string, mixed>   $options provider-validated
     * @param list<FilterDefinition> $filters
     * @param string                 $count   `auto`, `none` or a CountStrategyInterface service id
     */
    public function __construct(
        public string $code,
        public string $provider,
        public array $options = [],
        public int $perPage = 15,
        public int $maxPerPage = 100,
        public string $count = self::COUNT_AUTO,
        array $filters = [],
    ) {
        $names = [];
        foreach ($filters as $filter) {
            $name = $filter->publicName();
            if (isset($names[$name])) {
                throw new \InvalidArgumentException(\sprintf('List "%s": duplicate public filter name "%s".', $code, $name));
            }
            $names[$name] = true;
        }
        if ($perPage < 1 || $maxPerPage < $perPage) {
            throw new \InvalidArgumentException(\sprintf('List "%s": pagination requires 1 <= per_page <= max_per_page.', $code));
        }
        $this->filters = array_values($filters);
    }

    public function findFilter(string $publicName): ?FilterDefinition
    {
        foreach ($this->filters as $filter) {
            if ($filter->publicName() === $publicName) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'provider' => $this->provider,
            'options' => $this->options,
            'pagination' => ['per_page' => $this->perPage, 'max_per_page' => $this->maxPerPage],
            'count' => $this->count,
            'filters' => array_map(static fn (FilterDefinition $filter): array => $filter->toArray(), $this->filters),
        ];
    }

    /**
     * @param array<string, mixed> $data the shape produced by {@see toArray()}
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            provider: $data['provider'],
            options: $data['options'] ?? [],
            perPage: $data['pagination']['per_page'],
            maxPerPage: $data['pagination']['max_per_page'],
            count: $data['count'] ?? self::COUNT_AUTO,
            filters: array_map(FilterDefinition::fromArray(...), $data['filters'] ?? []),
        );
    }
}
