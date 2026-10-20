<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

/**
 * The data of an in-memory list: objects or arrays (nested arrays/objects for relations, lists for to-many).
 * Referenced by the `source` option of the list; tag `sidus_filter.in_memory_source`, autoconfigured.
 */
interface InMemorySourceInterface
{
    /** @return iterable<array<array-key, mixed>|object> */
    public function items(): iterable;
}
