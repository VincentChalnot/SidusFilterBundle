<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Registry;

use Psr\Container\ContainerInterface;
use Sidus\FilterBundle\Exception\UnknownFilterTypeException;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Filter\Type\BuiltInFilterTypes;
use Sidus\FilterBundle\Filter\Type\NativeFilterType;

/**
 * Filter types indexed by name, built once from the tagged iterator (no locator with an index method).
 * Immutable after construction.
 */
final class FilterTypeRegistry
{
    /** @var array<string, FilterTypeInterface>|null */
    private ?array $types = null;

    /**
     * @param iterable<FilterTypeInterface> $types
     */
    public function __construct(private readonly iterable $typeSource)
    {
    }

    /**
     * Every type shipped by the core, without a container (tests, provider contract suites).
     */
    public static function builtIn(?ContainerInterface $nativeBuilders = null): self
    {
        $types = [];
        foreach (BuiltInFilterTypes::classes() as $class) {
            $types[] = NativeFilterType::class === $class ? new NativeFilterType($nativeBuilders) : new $class();
        }

        return new self($types);
    }

    public function has(string $name): bool
    {
        return isset($this->index()[$name]);
    }

    public function get(string $name): FilterTypeInterface
    {
        return $this->index()[$name] ?? throw new UnknownFilterTypeException($name, $this->names());
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->index());
    }

    /**
     * @return array<string, FilterTypeInterface>
     */
    private function index(): array
    {
        if (null === $this->types) {
            $types = [];
            foreach ($this->typeSource as $type) {
                $name = $type::getName();
                if (isset($types[$name])) {
                    throw new \LogicException(\sprintf('Filter type "%s" is registered twice (%s, %s).', $name, $types[$name]::class, $type::class));
                }
                $types[$name] = $type;
            }
            ksort($types);
            $this->types = $types;
        }

        return $this->types;
    }
}
