<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Registry;

use Psr\Container\ContainerInterface;
use Sidus\FilterBundle\Exception\UnknownFilterTypeException;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Filter\Type\ChoiceFilterType;
use Sidus\FilterBundle\Filter\Type\DateRangeFilterType;
use Sidus\FilterBundle\Filter\Type\ExactFilterType;
use Sidus\FilterBundle\Filter\Type\NativeFilterType;
use Sidus\FilterBundle\Filter\Type\NotEmptyFilterType;
use Sidus\FilterBundle\Filter\Type\NotNullFilterType;
use Sidus\FilterBundle\Filter\Type\NullFilterType;
use Sidus\FilterBundle\Filter\Type\NumberFilterType;
use Sidus\FilterBundle\Filter\Type\RegexpFilterType;
use Sidus\FilterBundle\Filter\Type\SortFilterType;
use Sidus\FilterBundle\Filter\Type\TextFilterType;

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
        return new self([
            new TextFilterType(), new ExactFilterType(), new ChoiceFilterType(), new NumberFilterType(),
            new DateRangeFilterType(), new NullFilterType(), new NotNullFilterType(), new NotEmptyFilterType(),
            new RegexpFilterType(), new SortFilterType(), new NativeFilterType($nativeBuilders),
        ]);
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
