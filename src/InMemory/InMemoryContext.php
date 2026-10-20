<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Sidus\FilterBundle\Provider\QueryContextInterface;

/**
 * The per-request query: predicates and a comparator over the source, materialised once.
 */
final class InMemoryContext implements QueryContextInterface
{
    /** @var list<\Closure(mixed): bool> */
    private array $predicates = [];

    private ?\Closure $comparator = null;

    /** @var list<mixed>|null */
    private ?array $matching = null;

    /**
     * @param iterable<mixed> $source
     */
    public function __construct(private readonly iterable $source)
    {
    }

    public function addPredicate(\Closure $predicate): void
    {
        $this->predicates[] = $predicate;
    }

    public function sortBy(\Closure $comparator): void
    {
        $this->comparator = $comparator;
    }

    /**
     * @return list<mixed> the filtered then sorted items (PHP's sort is stable)
     */
    public function matching(): array
    {
        if (null === $this->matching) {
            $items = [];
            foreach ($this->source as $item) {
                if (array_all($this->predicates, static fn (\Closure $predicate): bool => $predicate($item))) {
                    $items[] = $item;
                }
            }
            if (null !== $this->comparator) {
                usort($items, $this->comparator);
            }
            $this->matching = $items;
        }

        return $this->matching;
    }
}
