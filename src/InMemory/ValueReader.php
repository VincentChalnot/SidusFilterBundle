<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Sidus\FilterBundle\Definition\Field;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads a {@see Field} from an item (array or object) with the PropertyAccessor. A to-many segment fans out:
 * the result holds one value per reachable terminal; a terminal collection is kept as one value
 * ({@see flatten()} gives "any element" semantics for comparisons). A missing relation yields null.
 */
final readonly class ValueReader
{
    private PropertyAccessorInterface $accessor;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @return list<mixed>
     */
    public function read(mixed $item, Field $field): array
    {
        $nodes = [$item];
        $segments = $field->segments();
        $last = \count($segments) - 1;
        foreach ($segments as $index => $segment) {
            $next = [];
            foreach ($nodes as $node) {
                $value = $this->property($node, $segment);
                if ($index < $last && self::isCollection($value)) {
                    foreach ($value as $element) {
                        $next[] = $element;
                    }
                } else {
                    $next[] = $value;
                }
            }
            $nodes = $next;
        }
        if ([] !== $field->json) {
            $nodes = array_map(fn (mixed $value): mixed => $this->json($value, $field->json), $nodes);
        }
        if (null !== $field->cast) {
            $nodes = array_map(static fn (mixed $value): mixed => self::cast($value, $field->cast), $nodes);
        }

        return $nodes;
    }

    /**
     * One element per leaf: terminal collections are expanded.
     *
     * @param list<mixed> $values
     *
     * @return list<mixed>
     */
    public static function flatten(array $values): array
    {
        $leaves = [];
        foreach ($values as $value) {
            if (self::isCollection($value)) {
                foreach ($value as $element) {
                    $leaves[] = $element;
                }
            } else {
                $leaves[] = $value;
            }
        }

        return $leaves;
    }

    public static function isCollection(mixed $value): bool
    {
        return (\is_array($value) && array_is_list($value))
            || ($value instanceof \Traversable && !$value instanceof \Stringable);
    }

    private function property(mixed $node, string $name): mixed
    {
        if (null === $node) {
            return null;
        }
        if (\is_array($node)) {
            return $node[$name] ?? null;
        }
        if ($node instanceof \ArrayAccess && isset($node[$name])) {
            return $node[$name];
        }
        if (\is_object($node) && $this->accessor->isReadable($node, $name)) {
            return $this->accessor->getValue($node, $name);
        }

        return null;
    }

    /**
     * @param list<string> $path
     */
    private function json(mixed $value, array $path): mixed
    {
        if (\is_string($value) && '' !== $value && \in_array($value[0], ['{', '['], true)) {
            $value = json_decode($value, true);
        }
        foreach ($path as $segment) {
            if (\is_object($value)) {
                $value = (array) $value;
            }
            if (!\is_array($value) || !\array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private static function cast(mixed $value, string $cast): mixed
    {
        if (null === $value) {
            return null;
        }

        return match ($cast) {
            'integer' => \is_int($value) || \is_bool($value) || (\is_string($value) && is_numeric($value)) ? (int) $value : null,
            'float' => \is_int($value) || \is_float($value) || (\is_string($value) && is_numeric($value)) ? (float) $value : null,
            'string' => \is_scalar($value) || $value instanceof \Stringable ? (string) $value : null,
            'boolean' => \is_scalar($value) ? filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) : null,
            'date' => $value instanceof \DateTimeInterface ? $value : self::parseDate($value),
            default => null,
        };
    }

    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        try {
            return \is_string($value) ? new \DateTimeImmutable($value) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
