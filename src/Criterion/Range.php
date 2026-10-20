<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/** Ordered comparison for numbers and dates. At least one bound is required. */
final readonly class Range extends FieldCriterion
{
    public function __construct(
        Field|string $field,
        public int|float|\DateTimeInterface|string|null $min = null,
        public int|float|\DateTimeInterface|string|null $max = null,
        public bool $minInclusive = true,
        public bool $maxInclusive = true,
    ) {
        if (null === $min && null === $max) {
            throw new \InvalidArgumentException('Range requires at least one bound.');
        }
        parent::__construct($field);
    }
}
