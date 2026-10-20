<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Input;

/**
 * @implements \IteratorAggregate<int, Violation>
 */
final readonly class ViolationList implements \IteratorAggregate, \Countable
{
    /** @var list<Violation> */
    private array $violations;

    public function __construct(Violation ...$violations)
    {
        $this->violations = array_values($violations);
    }

    public function with(Violation ...$violations): self
    {
        return new self(...$this->violations, ...$violations);
    }

    public function isEmpty(): bool
    {
        return [] === $this->violations;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->violations);
    }

    public function count(): int
    {
        return \count($this->violations);
    }

    /**
     * @return list<array{path: string, message: string, value: mixed}>
     */
    public function toArray(): array
    {
        return array_map(static fn (Violation $violation): array => $violation->toArray(), $this->violations);
    }
}
