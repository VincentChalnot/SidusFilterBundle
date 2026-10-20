<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Registry;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Exception\UnknownListException;

/**
 * The compiled list definitions, from configuration only (no `add*` method). They are dumped as arrays by the
 * compiler pass and rebuilt into readonly objects on first use; being immutable, the memo is safe in a worker.
 */
final class ListDefinitionRegistry
{
    /** @var array<string, ListDefinition> */
    private array $built = [];

    /**
     * @param array<string, array<string, mixed>> $definitions the {@see ListDefinition::toArray()} shape by code
     */
    public function __construct(private readonly array $definitions = [])
    {
    }

    public function has(string $code): bool
    {
        return isset($this->definitions[$code]);
    }

    public function get(string $code): ListDefinition
    {
        if (!isset($this->definitions[$code])) {
            throw new UnknownListException($code, $this->codes());
        }

        return $this->built[$code] ??= ListDefinition::fromArray($this->definitions[$code]);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->definitions);
    }
}
