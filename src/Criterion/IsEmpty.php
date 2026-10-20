<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/** Null, empty string, or empty collection. "Not empty" is `new Not(new IsEmpty(...))`. */
final readonly class IsEmpty extends FieldCriterion
{
}
