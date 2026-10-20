<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form;

/**
 * Form mappers indexed by filter type name, built once from the tagged iterator.
 */
final class FormMapperRegistry
{
    /** @var array<string, FormFilterMapperInterface>|null */
    private ?array $mappers = null;

    /**
     * @param iterable<FormFilterMapperInterface> $source
     */
    public function __construct(private readonly iterable $source)
    {
    }

    public function find(string $filterType): ?FormFilterMapperInterface
    {
        if (null === $this->mappers) {
            $mappers = [];
            foreach ($this->source as $mapper) {
                $mappers[$mapper->getFilterType()] = $mapper;
            }
            $this->mappers = $mappers;
        }

        return $this->mappers[$filterType] ?? null;
    }
}
