<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Input;

/**
 * Raw, untrusted input of one list request: filter values by public name, page, page size, and an optional total
 * supplied by the caller. Nothing is validated here: normalisation collects violations instead.
 */
final readonly class ListInput
{
    /**
     * @param array<string, mixed> $filters raw value by public filter name
     */
    public function __construct(
        public array $filters = [],
        public mixed $page = null,
        public mixed $perPage = null,
        public int|\Closure|null $totalCount = null,
    ) {
    }

    /**
     * Reads `filters`, `page` and `per_page`; a top-level `sort` is taken as the `sort` filter (API style `?sort=-date`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $filters = $data['filters'] ?? [];
        if (!\is_array($filters)) {
            $filters = [];
        }
        if (\array_key_exists('sort', $data) && !\array_key_exists('sort', $filters)) {
            $filters['sort'] = $data['sort'];
        }

        return new self($filters, $data['page'] ?? null, $data['per_page'] ?? null);
    }

    /**
     * Supplies the total instead of counting: an int, or a callable evaluated lazily and at most once, only if the
     * result's total is read.
     */
    public function withTotalCount(int|callable $total): self
    {
        if (\is_int($total) && $total < 0) {
            throw new \InvalidArgumentException('The total count cannot be negative.');
        }

        return new self($this->filters, $this->page, $this->perPage, \is_int($total) ? $total : $total(...));
    }
}
