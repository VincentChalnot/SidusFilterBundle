<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Input\Violation;

/**
 * Outcome of normalising one filter's raw input: a value, "empty" (the filter is inactive) or violations.
 */
final readonly class NormalizedValue
{
    /**
     * @param list<Violation> $violations
     */
    private function __construct(public mixed $value, private bool $empty, public array $violations)
    {
    }

    public static function of(mixed $value): self
    {
        return new self($value, false, []);
    }

    public static function empty(): self
    {
        return new self(null, true, []);
    }

    public static function invalid(Violation ...$violations): self
    {
        if ([] === $violations) {
            throw new \InvalidArgumentException('An invalid value requires at least one violation.');
        }

        return new self(null, false, array_values($violations));
    }

    public function isEmpty(): bool
    {
        return $this->empty;
    }

    public function isValid(): bool
    {
        return [] === $this->violations;
    }
}
