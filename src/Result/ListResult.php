<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Result;

use Sidus\FilterBundle\Input\ViolationList;

/**
 * The page of items with what produced it. The total is lazy: computed on the first {@see total()} call, once.
 */
final class ListResult
{
    private bool $totalResolved;

    private ?int $total;

    private ?\Closure $totalProvider;

    /**
     * @param list<mixed>                  $items
     * @param int|\Closure(): ?int|null    $total   null or a closure returning null = unknown
     * @param array<string, mixed>         $state   the normalised, applied state (what an adapter renders back)
     */
    public function __construct(
        private readonly array $items,
        private readonly int $page,
        private readonly int $perPage,
        int|\Closure|null $total,
        private readonly ViolationList $violations,
        private readonly array $state = [],
        private readonly bool $hasNextPage = false,
    ) {
        $this->totalResolved = !$total instanceof \Closure;
        $this->total = $total instanceof \Closure ? null : $total;
        $this->totalProvider = $total instanceof \Closure ? $total : null;
    }

    /** @return list<mixed> */
    public function items(): array
    {
        return $this->items;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    /** The total number of matching items, or null when unknown (count strategy `none`). */
    public function total(): ?int
    {
        if (!$this->totalResolved) {
            $total = ($this->totalProvider)();
            $this->total = $total;
            $this->totalResolved = true;
            $this->totalProvider = null;
        }

        return $this->total;
    }

    /** Whether another page exists: exact with `count: none`, derived from the total otherwise. */
    public function hasNextPage(): bool
    {
        $total = $this->hasNextPage ? null : $this->total();

        return $this->hasNextPage || (null !== $total && $this->page * $this->perPage < $total);
    }

    /** @return int|null the number of pages, null when the total is unknown */
    public function pages(): ?int
    {
        $total = $this->total();

        return null === $total ? null : max(1, (int) ceil($total / $this->perPage));
    }

    public function violations(): ViolationList
    {
        return $this->violations;
    }

    /** @return array<string, mixed> */
    public function state(): array
    {
        return $this->state;
    }
}
