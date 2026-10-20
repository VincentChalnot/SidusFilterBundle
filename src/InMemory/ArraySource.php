<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

final readonly class ArraySource implements InMemorySourceInterface
{
    /**
     * @param list<array<array-key, mixed>|object> $items
     */
    public function __construct(private array $items)
    {
    }

    public function items(): iterable
    {
        return $this->items;
    }
}
